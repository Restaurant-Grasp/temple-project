<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\BookingMeta;
use App\Models\BookingPayment;
use App\Models\PaymentMode;
use App\Models\SaleItem;
use App\Services\InventoryMigrationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class SalesController extends Controller
{
    /**
     * Inventory migration service
     *
     * @var InventoryMigrationService
     */
    protected $inventoryMigrationService;

    /**
     * Constructor
     *
     * @param InventoryMigrationService $inventoryMigrationService
     */
    public function __construct(InventoryMigrationService $inventoryMigrationService)
    {
        $this->inventoryMigrationService = $inventoryMigrationService;
    }
    /**
     * Booking type constant
     */
    const BOOKING_TYPE = 'SALES';
    
    /**
     * Booking number prefix (SLBD for Development, SLBL for Live)
     */
    const BOOKING_PREFIX_DEV = 'SLBD';
    const BOOKING_PREFIX_LIVE = 'SLBL';
    
    /**
     * Payment reference prefix (PYD for Development, PYL for Live)
     */
    const PAYMENT_PREFIX_DEV = 'PYD';
    const PAYMENT_PREFIX_LIVE = 'PYL';

    /**
     * Store a new POS Sales order
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        // Validate request
        $validator = Validator::make($request->all(), [
            'booking_date' => 'required|date',
            'subtotal' => 'required|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'total_amount' => 'required|numeric|min:0',
            'paid_amount' => 'required|numeric|min:0',
            'deposit_amount' => 'nullable|numeric|min:0',
            'print_option' => 'required|in:NO_PRINT,SINGLE_PRINT,SEP_PRINT',
            'special_instructions' => 'nullable|string',
            
            // Items validation
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|integer',
            'items.*.deity_id' => 'nullable|integer',
            'items.*.name_primary' => 'required|string|max:255',
            'items.*.name_secondary' => 'nullable|string|max:255',
            'items.*.short_code' => 'nullable|string|max:50',
            'items.*.sale_type' => 'required|string|max:50',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.total' => 'required|numeric|min:0',
            'items.*.vehicles' => 'nullable|array',
            
            // Devotee details (optional)
            'devotee.name' => 'nullable|string|max:255',
            'devotee.email' => 'nullable|email|max:255',
            'devotee.nric' => 'nullable|string|max:50',
            'devotee.phone_code' => 'nullable|string|max:10',
            'devotee.phone' => 'nullable|string|max:50',
            'devotee.dob' => 'nullable|date',
            'devotee.address' => 'nullable|string',
            'devotee.remarks' => 'nullable|string',
            
            // Payment validation
            'payment.amount' => 'required|numeric|min:0',
            'payment.payment_mode_id' => 'required|exists:payment_modes,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {
            $user = $request->user();
            $isLive = config('app.env') === 'production';
            
            // Generate booking number
            $bookingNumber = $this->generateBookingNumber($isLive);
            
            // Get payment mode name for reference
            $paymentMode = PaymentMode::find($request->input('payment.payment_mode_id'));
            
            // Determine payment status
            $paidAmount = $request->input('paid_amount');
            $totalAmount = $request->input('total_amount');
            $booking_through = !empty($request->input('booking_through')) ? $request->input('booking_through') : 'ADMIN';
            $paymentStatus = $this->determinePaymentStatus($paidAmount, $totalAmount);
            
            // Create booking record
            $booking = Booking::create([
                'booking_number' => $bookingNumber,
                'booking_type' => self::BOOKING_TYPE,
                'devotee_id' => null,
                'booking_date' => Carbon::parse($request->input('booking_date')),
                'booking_status' => 'CONFIRMED',
                'payment_status' => $paymentStatus,
                'subtotal' => $request->input('subtotal'),
                'tax_amount' => 0,
                'discount_amount' => $request->input('discount_amount', 0),
                'deposit_amount' => $request->input('deposit_amount', 0),
                'total_amount' => $totalAmount,
                'paid_amount' => $paidAmount,
                'payment_method' => $paymentMode ? $paymentMode->name : null,
                'print_option' => $request->input('print_option'),
                'special_instructions' => $request->input('special_instructions'),
                'booking_through' => $booking_through,
                'commission_migration' => 0,
                'inventory_migration' => 0,
                'account_migration' => 0,
                'created_by' => $user->id,
                  'user_id' => $user->id, 
            ]);

            // Create booking items
            $items = $request->input('items');
            foreach ($items as $item) {
                $bookingItem = BookingItem::create([
                    'booking_id' => $booking->id,
                    'item_type' => $item['sale_type'],
                    'item_id' => $item['id'],
                    'deity_id' => $item['deity_id'] ?? null,
                    'item_name' => $item['name_primary'],
                    'item_name_secondary' => $item['name_secondary'] ?? null,
                    'short_code' => $item['short_code'] ?? null,
                    'service_date' => Carbon::parse($request->input('booking_date')),
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['price'],
                    'total_price' => $item['total'],
                    'status' => 'COMPLETED',
                    'notes' => null,
                ]);

                // Save vehicle details to booking_item_meta if present
                if (!empty($item['vehicles']) && is_array($item['vehicles'])) {
                    foreach ($item['vehicles'] as $index => $vehicle) {
                        // Save vehicle number
                        if (!empty($vehicle['number'])) {
                            DB::table('booking_item_meta')->insert([
                                'id' => \Illuminate\Support\Str::uuid()->toString(),
                                'booking_item_id' => $bookingItem->id,
                                'meta_key' => 'vehicle_number_' . ($index + 1),
                                'meta_value' => $vehicle['number'],
                                'meta_type' => 'STRING',
                                'created_at' => now(),
                            ]);
                        }
                        
                        // Save vehicle type
                        if (!empty($vehicle['type'])) {
                            DB::table('booking_item_meta')->insert([
                                'id' => \Illuminate\Support\Str::uuid()->toString(),
                                'booking_item_id' => $bookingItem->id,
                                'meta_key' => 'vehicle_type_' . ($index + 1),
                                'meta_value' => $vehicle['type'],
                                'meta_type' => 'STRING',
                                'created_at' => now(),
                            ]);
                        }
                        
                        // Save vehicle owner
                        if (!empty($vehicle['owner'])) {
                            DB::table('booking_item_meta')->insert([
                                'id' => \Illuminate\Support\Str::uuid()->toString(),
                                'booking_item_id' => $bookingItem->id,
                                'meta_key' => 'vehicle_owner_' . ($index + 1),
                                'meta_value' => $vehicle['owner'],
                                'meta_type' => 'STRING',
                                'created_at' => now(),
                            ]);
                        }
                    }
                    
                    // Save vehicle count
                    DB::table('booking_item_meta')->insert([
                        'id' => \Illuminate\Support\Str::uuid()->toString(),
                        'booking_item_id' => $bookingItem->id,
                        'meta_key' => 'vehicle_count',
                        'meta_value' => count($item['vehicles']),
                        'meta_type' => 'INTEGER',
                        'created_at' => now(),
                    ]);
                }
            }

            // Create booking meta records for devotee details (optional)
            $devoteeData = $request->input('devotee');
            if ($devoteeData && $this->hasDevoteeData($devoteeData)) {
                $metaRecords = [
                    ['meta_key' => 'devotee_name', 'meta_value' => $devoteeData['name'] ?? '', 'meta_type' => 'STRING'],
                    ['meta_key' => 'devotee_email', 'meta_value' => $devoteeData['email'] ?? '', 'meta_type' => 'STRING'],
                    ['meta_key' => 'devotee_nric', 'meta_value' => $devoteeData['nric'] ?? '', 'meta_type' => 'STRING'],
                    ['meta_key' => 'devotee_phone_code', 'meta_value' => $devoteeData['phone_code'] ?? '+60', 'meta_type' => 'STRING'],
                    ['meta_key' => 'devotee_phone', 'meta_value' => $devoteeData['phone'] ?? '', 'meta_type' => 'STRING'],
                    ['meta_key' => 'devotee_dob', 'meta_value' => $devoteeData['dob'] ?? '', 'meta_type' => 'DATE'],
                    ['meta_key' => 'devotee_address', 'meta_value' => $devoteeData['address'] ?? '', 'meta_type' => 'STRING'],
                    ['meta_key' => 'devotee_remarks', 'meta_value' => $devoteeData['remarks'] ?? '', 'meta_type' => 'STRING'],
                ];

                foreach ($metaRecords as $meta) {
                    if (!empty($meta['meta_value'])) {
                        BookingMeta::create([
                            'booking_id' => $booking->id,
                            'meta_key' => $meta['meta_key'],
                            'meta_value' => $meta['meta_value'],
                            'meta_type' => $meta['meta_type'],
                            'created_at' => now(),
                        ]);
                    }
                }
            }

            // Generate payment reference
            $paymentReference = $this->generatePaymentReference($isLive);
            
            // Create payment record
            BookingPayment::create([
                'booking_id' => $booking->id,
                'payment_date' => now(),
                'amount' => $request->input('payment.amount'),
                'payment_mode_id' => $request->input('payment.payment_mode_id'),
                'payment_method' => $paymentMode ? $paymentMode->name : null,
                'payment_reference' => $paymentReference,
                'paid_through' => $booking_through,
                'payment_type' => 'FULL',
                'payment_status' => 'SUCCESS',
                'created_by' => $user->id,
            ]);

            // Process inventory migration for items with BOM products
            try {
                $inventoryResult = $this->inventoryMigrationService->processInventoryMigration(
                    $booking,
                    $items
                );
                
                Log::info('Inventory migration processed', [
                    'booking_number' => $booking->booking_number,
                    'result' => $inventoryResult['message'],
                    'movements_count' => count($inventoryResult['movements'])
                ]);
            } catch (\Exception $inventoryException) {
                // Rollback and throw inventory error
                DB::rollBack();
                
                Log::error('Inventory migration failed', [
                    'booking_number' => $booking->booking_number,
                    'error' => $inventoryException->getMessage()
                ]);

                return response()->json([
                    'success' => false,
                    'message' => $inventoryException->getMessage(),
                    'error_type' => 'INVENTORY_ERROR'
                ], 400);
            }
             // ADD ACCOUNT MIGRATION HERE
            try {
                $this->accountMigration($booking->id);
                
                Log::info('Account migration processed', [
                    'booking_number' => $booking->booking_number
                ]);
            } catch (\Exception $accountException) {
                DB::rollBack();
                
                Log::error('Account migration failed', [
                    'booking_number' => $booking->booking_number,
                    'error' => $accountException->getMessage()
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Account migration failed: ' . $accountException->getMessage(),
                    'error_type' => 'ACCOUNT_ERROR'
                ], 400);
            }


            DB::commit();

            // Load relationships for response
            $booking->load(['items', 'meta', 'payments']);

            // Format response data
            $responseData = $this->formatBookingResponse($booking);

            return response()->json([
                'success' => true,
                'message' => 'Sales order created successfully',
                'data' => $responseData
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('POS Sales order creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create sales order: ' . $e->getMessage(),
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred'
            ], 500);
        }
    }

    /**
     * Get a single Sales booking
     *
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            $booking = Booking::with(['items', 'meta', 'payments', 'creator'])
                ->where(function($query) use ($id) {
                    $query->where('id', $id)
                          ->orWhere('booking_number', $id);
                })
                ->where('booking_type', self::BOOKING_TYPE)
                ->first();

            if (!$booking) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sales order not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $this->formatBookingResponse($booking)
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to fetch Sales order', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch sales order'
            ], 500);
        }
    }

    /**
     * List all Sales bookings
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 15);
            $search = $request->input('search');
            $status = $request->input('status');
            $paymentStatus = $request->input('payment_status');
            $fromDate = $request->input('from_date');
            $toDate = $request->input('to_date');

            $query = Booking::with(['items', 'meta', 'payments', 'creator'])
                ->where('booking_type', self::BOOKING_TYPE)
                ->orderBy('created_at', 'desc');

            // Apply filters
            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('booking_number', 'ILIKE', "%{$search}%")
                      ->orWhereHas('meta', function($mq) use ($search) {
                          $mq->where('meta_key', 'devotee_name')
                             ->where('meta_value', 'ILIKE', "%{$search}%");
                      });
                });
            }

            if ($status) {
                $query->where('booking_status', $status);
            }

            if ($paymentStatus) {
                $query->where('payment_status', $paymentStatus);
            }

            if ($fromDate) {
                $query->whereDate('booking_date', '>=', $fromDate);
            }

            if ($toDate) {
                $query->whereDate('booking_date', '<=', $toDate);
            }

            $bookings = $query->paginate($perPage);

            // Format response
            $formattedBookings = $bookings->getCollection()->map(function($booking) {
                return $this->formatBookingResponse($booking);
            });

            return response()->json([
                'success' => true,
                'data' => $formattedBookings,
                'pagination' => [
                    'current_page' => $bookings->currentPage(),
                    'last_page' => $bookings->lastPage(),
                    'per_page' => $bookings->perPage(),
                    'total' => $bookings->total()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to fetch Sales bookings', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch sales orders'
            ], 500);
        }
    }

    /**
     * Cancel a Sales booking
     *
     * @param string $id
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function cancel($id, Request $request)
    {
        DB::beginTransaction();
        
        try {
            $booking = Booking::where('id', $id)
                ->where('booking_type', self::BOOKING_TYPE)
                ->first();

            if (!$booking) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sales order not found'
                ], 404);
            }

            if ($booking->booking_status === 'CANCELLED') {
                return response()->json([
                    'success' => false,
                    'message' => 'Sales order is already cancelled'
                ], 400);
            }

            if ($booking->booking_status === 'COMPLETED') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot cancel a completed sales order'
                ], 400);
            }

            // Reverse inventory migration if it was processed
            if ($booking->inventory_migration === 1) {
                try {
                    $reversalResult = $this->inventoryMigrationService->reverseInventoryMigration($booking);
                    
                    Log::info('Inventory reversal processed for cancellation', [
                        'booking_number' => $booking->booking_number,
                        'result' => $reversalResult['message'],
                        'movements_count' => count($reversalResult['movements'])
                    ]);
                } catch (\Exception $reversalException) {
                    DB::rollBack();
                    
                    Log::error('Inventory reversal failed during cancellation', [
                        'booking_number' => $booking->booking_number,
                        'error' => $reversalException->getMessage()
                    ]);

                    return response()->json([
                        'success' => false,
                        'message' => 'Failed to reverse inventory: ' . $reversalException->getMessage(),
                        'error_type' => 'INVENTORY_REVERSAL_ERROR'
                    ], 500);
                }
            }

            $booking->update([
                'booking_status' => 'CANCELLED',
                'updated_at' => now()
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Sales order cancelled successfully',
                'data' => $this->formatBookingResponse($booking->fresh(['items', 'meta', 'payments']))
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to cancel Sales order', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel sales order'
            ], 500);
        }
    }

    /**
     * Generate booking number
     *
     * @param bool $isLive
     * @return string
     */
    private function generateBookingNumber($isLive = false)
    {
        $prefix = $isLive ? self::BOOKING_PREFIX_LIVE : self::BOOKING_PREFIX_DEV;
        $date = Carbon::now()->format('Ymd');
        
        // Get the last booking number for today with this prefix
        $lastBooking = Booking::where('booking_number', 'LIKE', $prefix . $date . '%')
            ->orderBy('booking_number', 'desc')
            ->first();

        if ($lastBooking) {
            // Extract the sequence number and increment
            $lastNumber = (int) substr($lastBooking->booking_number, -8);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . $date . str_pad($newNumber, 8, '0', STR_PAD_LEFT);
    }

    /**
     * Generate payment reference
     *
     * @param bool $isLive
     * @return string
     */
    private function generatePaymentReference($isLive = false)
    {
        $prefix = $isLive ? self::PAYMENT_PREFIX_LIVE : self::PAYMENT_PREFIX_DEV;
        $date = Carbon::now()->format('Ymd');
        
        // Get the last payment reference for today
        $lastPayment = BookingPayment::where('payment_reference', 'LIKE', $prefix . $date . '%')
            ->orderBy('payment_reference', 'desc')
            ->first();

        if ($lastPayment) {
            $lastNumber = (int) substr($lastPayment->payment_reference, -8);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . $date . str_pad($newNumber, 8, '0', STR_PAD_LEFT);
    }

    /**
     * Determine payment status based on paid amount
     *
     * @param float $paidAmount
     * @param float $totalAmount
     * @return string
     */
    private function determinePaymentStatus($paidAmount, $totalAmount)
    {
        if ($paidAmount <= 0) {
            return 'PENDING';
        } elseif ($paidAmount < $totalAmount) {
            return 'PARTIAL';
        } else {
            return 'FULL';
        }
    }

    /**
     * Check if devotee data has any filled values
     *
     * @param array $devoteeData
     * @return bool
     */
    private function hasDevoteeData($devoteeData)
    {
        if (!$devoteeData) return false;
        
        foreach ($devoteeData as $key => $value) {
            if (!empty($value)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Format booking response
     *
     * @param Booking $booking
     * @return array
     */
    private function formatBookingResponse($booking)
    {
        // Convert meta to key-value pairs
        $metaData = [];
        if ($booking->meta) {
            foreach ($booking->meta as $meta) {
                $metaData[$meta->meta_key] = $meta->meta_value;
            }
        }

        // Format items
        $items = [];
        if ($booking->items) {
            foreach ($booking->items as $item) {
                $itemData = [
                    'id' => $item->id,
                    'item_id' => $item->item_id,
                    'deity_id' => $item->deity_id,
                    'item_type' => $item->item_type,
                    'item_name' => $item->item_name,
                    'item_name_secondary' => $item->item_name_secondary,
                    'short_code' => $item->short_code,
                    'quantity' => $item->quantity,
                    'unit_price' => (float) $item->unit_price,
                    'total_price' => (float) $item->total_price,
                    'status' => $item->status,
                ];
                $items[] = $itemData;
            }
        }

        // Get latest payment info
        $latestPayment = $booking->payments ? $booking->payments->first() : null;

        return [
            'id' => $booking->id,
            'booking_number' => $booking->booking_number,
            'booking_type' => $booking->booking_type ?? self::BOOKING_TYPE,
            'booking_date' => $booking->booking_date ? $booking->booking_date->format('Y-m-d') : null,
            'booking_status' => $booking->booking_status,
            'payment_status' => $booking->payment_status,
            'subtotal' => (float) $booking->subtotal,
            'discount_amount' => (float) $booking->discount_amount,
            'deposit_amount' => (float) $booking->deposit_amount,
            'total_amount' => (float) $booking->total_amount,
            'paid_amount' => (float) $booking->paid_amount,
            'balance_amount' => (float) ($booking->total_amount - $booking->paid_amount),
            'print_option' => $booking->print_option,
            'special_instructions' => $booking->special_instructions,
            
            // Items
            'items' => $items,
            'items_count' => count($items),
            
            // Devotee data
            'devotee' => [
                'name' => $metaData['devotee_name'] ?? null,
                'email' => $metaData['devotee_email'] ?? null,
                'nric' => $metaData['devotee_nric'] ?? null,
                'phone_code' => $metaData['devotee_phone_code'] ?? '+60',
                'phone' => $metaData['devotee_phone'] ?? null,
                'dob' => $metaData['devotee_dob'] ?? null,
                'address' => $metaData['devotee_address'] ?? null,
                'remarks' => $metaData['devotee_remarks'] ?? null,
            ],
            
            // Payment info
            'payment' => $latestPayment ? [
                'id' => $latestPayment->id,
                'amount' => (float) $latestPayment->amount,
                'payment_reference' => $latestPayment->payment_reference,
                'payment_method' => $latestPayment->payment_method,
                'payment_status' => $latestPayment->payment_status,
                'payment_date' => $latestPayment->payment_date ? $latestPayment->payment_date->format('Y-m-d H:i:s') : null,
            ] : null,
            
            // Timestamps
            'created_at' => $booking->created_at ? $booking->created_at->format('Y-m-d H:i:s') : null,
            'updated_at' => $booking->updated_at ? $booking->updated_at->format('Y-m-d H:i:s') : null,
            'created_by' => $booking->creator ? [
                'id' => $booking->creator->id,
                'name' => $booking->creator->name ?? $booking->creator->username,
            ] : null,
            'user' => $booking->user ? [
    'id' => $booking->user->id,
    'name' => $booking->user->name ?? $booking->user->username,
] : null,
        ];
    }

     protected function accountMigration($bookingId)
    {
        try {
            Log::info('Starting account migration for sales', ['booking_id' => $bookingId]);

            // Get booking details with relationships
            $booking = Booking::with(['bookingPayments.paymentMode', 'bookingItems'])
                ->findOrFail($bookingId);

            // Get payment details
            $payment = $booking->bookingPayments->first();
            if (!$payment) {
                throw new \Exception('No payment found for sales order');
            }

            $paymentMode = $payment->paymentMode;
            if (!$paymentMode) {
                throw new \Exception('Payment mode not found');
            }

            // Check if payment mode has ledger_id (DEBIT side - Asset/Bank)
            if (empty($paymentMode->ledger_id)) {
                Log::warning('Payment mode does not have ledger_id', [
                    'payment_mode_id' => $paymentMode->id,
                    'payment_mode_name' => $paymentMode->name
                ]);
                throw new \Exception('Payment mode ledger configuration missing');
            }

            $debitLedgerId = $paymentMode->ledger_id;

            // Get booking items for credit entries (CREDIT side - Income)
            $bookingItems = $booking->bookingItems;
            if ($bookingItems->isEmpty()) {
                throw new \Exception('No items found in sales order');
            }

            // Prepare credit entries array
            $creditEntries = [];
            $totalCreditAmount = 0;
			
            foreach ($bookingItems as $bookingItem) {
                // Get sale item details to find ledger
                $saleItem = SaleItem::find($bookingItem->item_id);
                
                $creditLedgerId = null;

                if ($saleItem && !empty($saleItem->ledger_id)) {
                    // Use sale item's configured ledger
                    $creditLedgerId = $saleItem->ledger_id;
                } else {
                    // Get or create "Sales Income" ledger under Incomes group (8000)
                    $creditLedgerId = $this->getOrCreateSalesIncomeLedger();
                }

                // Add to credit entries
                $itemTotal = $bookingItem->total_price;
                $creditEntries[] = [
                    'ledger_id' => $creditLedgerId,
                    'amount' => $itemTotal,
                    'details' => "Sale: {$bookingItem->item_name} ({$booking->booking_number})"
                ];

                $totalCreditAmount += $itemTotal;
            }
			
            // Verify amounts match
            if (abs($booking->paid_amount - $totalCreditAmount) > 0.01) {
                Log::warning('Amount mismatch in sales migration', [
                    'paid_amount' => $booking->paid_amount,
                    'total_credit' => $totalCreditAmount
                ]);
            }
			
			$settings = DB::table('booking_settings')
                ->whereIn('key', ['deposit_ledger_id', 'discount_ledger_id'])
                ->pluck('value', 'key');
			$depositLedgerId = $settings['deposit_ledger_id'] ?? null;
            $discountLedgerId = $settings['discount_ledger_id'] ?? null;
            // Generate entry code
            $date = $booking->booking_date;
            $year = $date->format('y');
            $month = $date->format('m');

            // Get last entry code for the month
            $lastEntry = DB::table('entries')
                ->whereYear('date', $date->format('Y'))
                ->whereMonth('date', $month)
                ->where('entrytype_id', 1) // Receipt entry type
                ->orderBy('id', 'desc')
                ->first();

            $lastNumber = 0;
            if ($lastEntry && !empty($lastEntry->entry_code)) {
                $lastNumber = (int)substr($lastEntry->entry_code, -5);
            }

            $entryCode = 'REC' . $year . $month . str_pad($lastNumber + 1, 5, '0', STR_PAD_LEFT);

            // Prepare narration
            $itemCount = $bookingItems->count();
            $devoteeInfo = $this->getDevoteeInfoFromMeta($booking);
            
            $narration = "POS Sales ({$booking->booking_number})\n";
            $narration .= "Items: {$itemCount}\n";
            if (!empty($devoteeInfo['name'])) {
                $narration .= "Customer: {$devoteeInfo['name']}\n";
            }
            if (!empty($devoteeInfo['nric'])) {
                $narration .= "NRIC: {$devoteeInfo['nric']}\n";
            }

            // Create entry
            $entryId = DB::table('entries')->insertGetId([
                'entrytype_id' => 1, // Receipt type
                'number' => $entryCode,
                'date' => $date,
                'dr_total' => $booking->subtotal,
                'cr_total' => $booking->subtotal,
                'narration' => $narration,
                'inv_id' => $bookingId,
                'inv_type' => 3, // Sales type (you may need to define this)
                'entry_code' => $entryCode,
                'created_by' => auth()->id(),
                    'user_id' => auth()->id(),
                'created_at' => now(),
                'updated_at' => now()
            ]);

            Log::info('Entry created for sales', [
                'entry_id' => $entryId,
                'entry_code' => $entryCode
            ]);
			
			// Create DEBIT entry item (Discount ledger - Expense increase)
            DB::table('entryitems')->insert([
                'entry_id' => $entryId,
                'ledger_id' => $discountLedgerId,
                'amount' => $booking->discount_amount,
                'details' => "POS Sales Discount ({$booking->booking_number})",
                'is_discount' => 1,
                'dc' => 'D', // Debit
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // Create DEBIT entry item (Payment mode ledger - Asset increase)
            DB::table('entryitems')->insert([
                'entry_id' => $entryId,
                'ledger_id' => $debitLedgerId,
                'amount' => $booking->paid_amount,
                'details' => "POS Sales ({$booking->booking_number})",
                'dc' => 'D', // Debit
                'created_at' => now(),
                'updated_at' => now()
            ]);

            Log::info('Debit entry item created for sales', [
                'ledger_id' => $debitLedgerId,
                'amount' => $booking->paid_amount
            ]);

            // Create CREDIT entry items (Income ledgers - Income increase)
            foreach ($creditEntries as $creditEntry) {
                DB::table('entryitems')->insert([
                    'entry_id' => $entryId,
                    'ledger_id' => $creditEntry['ledger_id'],
                    'amount' => $creditEntry['amount'],
                    'details' => $creditEntry['details'],
                    'dc' => 'C', // Credit
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                Log::info('Credit entry item created for sales', [
                    'ledger_id' => $creditEntry['ledger_id'],
                    'amount' => $creditEntry['amount']
                ]);
            }

            // Update booking to mark account migration as complete
            $booking->update(['account_migration' => 1]);

            Log::info('Account migration completed successfully for sales', [
                'booking_id' => $bookingId,
                'entry_id' => $entryId
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Error in sales account migration', [
                'booking_id' => $bookingId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
      private function getOrCreateSalesIncomeLedger()
    {
        // Get or create "Incomes" group
        $incomesGroup = DB::table('groups')->where('code', '8000')->first();

        if (!$incomesGroup) {
            // Create Incomes group if it doesn't exist
            $incomesGroupId = DB::table('groups')->insertGetId([
                'parent_id' => 0,
                'name' => 'Incomes',
                'code' => '8000',
                'added_by' => auth()->id(),
                'created_at' => now(),
                'updated_at' => now()
            ]);
        } else {
            $incomesGroupId = $incomesGroup->id;
        }

        // Get or create "Sales Income" ledger
        $salesIncomeLedger = DB::table('ledgers')
            ->where('name', 'Sales Income')
            ->where('group_id', $incomesGroupId)
            ->first();

        if (!$salesIncomeLedger) {
            // Get the next right_code for this group
            $lastRightCode = DB::table('ledgers')
                ->where('group_id', $incomesGroupId)
                ->where('left_code', '8000')
                ->orderBy('right_code', 'desc')
                ->value('right_code');

            $newRightCode = $lastRightCode ? str_pad(((int)$lastRightCode + 1), 4, '0', STR_PAD_LEFT) : '0001';

            $salesIncomeLedgerId = DB::table('ledgers')->insertGetId([
                'group_id' => $incomesGroupId,
                'name' => 'Sales Income',
                'left_code' => '8000',
                'right_code' => $newRightCode,
                'type' => 0,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        } else {
            $salesIncomeLedgerId = $salesIncomeLedger->id;
        }

        return $salesIncomeLedgerId;
    }

    /**
     * Get devotee information from booking meta
     *
     * @param Booking $booking
     * @return array
     */
    private function getDevoteeInfoFromMeta($booking)
    {
        $meta = $booking->bookingMeta->pluck('meta_value', 'meta_key');
        
        return [
            'name' => $meta['devotee_name'] ?? '',
            'nric' => $meta['devotee_nric'] ?? '',
            'email' => $meta['devotee_email'] ?? '',
            'phone' => $meta['devotee_phone'] ?? ''
        ];
    }
}
# 🗺️ Frontend Navigation & Function Flow Guide
**Module:** Special Occasions - Relocation & QR Code System  
**Date:** 2026-01-12

---

## 📍 How to Access Features in the Site

### **Main Entry Point**
```
URL: https://temple2.chinesetemplesystems.xyz/temple1/special-occasions/relocation-report
```

---

## 🧭 Site Navigation Map

### **1. Dashboard → Special Occasions**
```
┌─────────────────────────────────────────────────────────┐
│ Dashboard (Home)                                        │
│  └─ Sidebar Menu                                        │
│      └─ 🎉 Temple Events (Special Occasions)           │
│          ├─ 📋 Event Master (Create/Edit Events)       │
│          ├─ 📝 Booking List (View All Bookings)        │
│          ├─ 📊 Reports                                  │
│          └─ 🔄 Relocation Report ← YOU ARE HERE        │
└─────────────────────────────────────────────────────────┘
```

---

## 📂 Frontend File Structure

### **Key JavaScript Files**

```
temple2/js/pages/special-occasions/
├── master.js              ← Event creation/editing
├── index.js               ← Booking list & relocation UI
├── relocation-report.js   ← Relocation report (FIXED TODAY)
├── create.js              ← New booking creation
├── report.js              ← General reports
└── services.js            ← Addon services management
```

---

## 🔄 Complete Function Flow

### **FLOW 1: Enable Relocation for an Event**

**File:** `temple2/js/pages/special-occasions/master.js`

```javascript
// Step 1: Navigate to Event Master
URL: /temple1/special-occasions/master

// Step 2: Create or Edit Event
┌─────────────────────────────────────────────────────────┐
│ Event Form                                              │
│  ├─ Event Name (Primary)                               │
│  ├─ Event Name (Secondary)                             │
│  ├─ Event Options/Packages                             │
│  ├─ ☑️ Enable Table Assignment  ← Checkbox             │
│  ├─ ☑️ Enable Relocation        ← Checkbox (Line 1445) │
│  └─ Table Layouts Configuration                        │
│      ├─ Table Name                                      │
│      ├─ Rows                                            │
│      ├─ Columns                                         │
│      ├─ Start Number                                    │
│      └─ Numbering Pattern (row-wise/column-wise)       │
└─────────────────────────────────────────────────────────┘

// Code Location: master.js
Line 1069: const enableRelocation = data.enable_relocation || false;
Line 1445: enable_relocation: $('#enableRelocation').is(':checked'),
```

**Function Flow:**
```
User Action → Check "Enable Relocation" checkbox
    ↓
master.js captures checkbox state (Line 1445)
    ↓
Sends to API: POST /api/v1/special-occasions
    ↓
Backend saves to: special_occ_master.enable_relocation
    ↓
Feature enabled for this event ✅
```

---

### **FLOW 2: View Bookings & Relocate Seats**

**File:** `temple2/js/pages/special-occasions/index.js`

```javascript
// Step 1: Navigate to Booking List
URL: /temple1/special-occasions/index

// Step 2: Booking List Table
┌─────────────────────────────────────────────────────────────────┐
│ Booking List                                                    │
│ ┌─────┬──────────┬─────────┬──────────┬────────┬─────────────┐ │
│ │ No. │ Booking# │ Devotee │ Event    │ Seat   │ Actions     │ │
│ ├─────┼──────────┼─────────┼──────────┼────────┼─────────────┤ │
│ │ 1   │ TEBD001  │ John    │ Kosanda  │ A1     │ 👁️ 🔄 📄   │ │
│ │     │          │         │          │        │ View│Relocate│Receipt│
│ └─────┴──────────┴─────────┴──────────┴────────┴─────────────┘ │
│                                                                 │
│ 🔄 Icon only shows if enable_relocation = true (Line 634)     │
└─────────────────────────────────────────────────────────────────┘

// Code Location: index.js
Line 530: enable_relocation: booking.enable_relocation || false,
Line 634: const showRelocationIcon = row.enable_relocation === true;
Line 1121: if (booking.enable_relocation) { /* show icon */ }
```

**Function Flow:**
```
Page Load → index.js loads bookings
    ↓
Line 530: Checks each booking's enable_relocation flag
    ↓
Line 634: Conditionally renders 🔄 relocation icon
    ↓
User clicks 🔄 icon
    ↓
Opens relocation modal (Line 753)
    ↓
Shows current seat info
    ↓
User selects new seat
    ↓
Calls API: POST /api/v1/special-occasion-bookings/{id}/relocate
    ↓
Backend updates seat & logs change
    ↓
Table refreshes with new seat ✅
```

---

### **FLOW 3: Relocate a Booking**

**File:** `temple2/js/pages/special-occasions/index.js`

```javascript
// Relocation Modal Structure
┌─────────────────────────────────────────────────────────┐
│ 🔄 Relocate Booking                                     │
│ ─────────────────────────────────────────────────────── │
│                                                         │
│ Current Information:                                    │
│  Booking: TEBD2025121600000006                         │
│  Current Seat: A1 (Table 1, R1C1)                      │
│                                                         │
│ New Location:                                           │
│  Table Number:    [Dropdown ▼]                         │
│  Row Number:      [Input: 1  ]                         │
│  Column Number:   [Input: 2  ]                         │
│  Seat Number:     [Input: A2 ]                         │
│                                                         │
│ Reason for Change: [Required]                          │
│  [Text area: e.g., "Devotee requested window seat"]   │
│                                                         │
│ Change Type:                                            │
│  ○ Manual Relocation                                   │
│  ○ Forced (Override existing)                          │
│                                                         │
│ ☑️ I confirm this relocation                           │
│                                                         │
│ [Cancel]  [Relocate Booking]                           │
└─────────────────────────────────────────────────────────┘

// Code Locations:
Line 294: HTML structure for modal
Line 305: Hidden input for booking ID
Line 753: Populate modal with booking data
Line 796: Handle relocation submission
```

**JavaScript Function Flow:**
```javascript
// 1. User clicks relocation icon
$('.btn-relocate').on('click', function() {
    const bookingId = $(this).data('booking-id');
    
    // 2. Load booking details (Line 753)
    $('#relocateBookingId').val(booking.id);
    $('#relocateBookingNo').text(booking.booking_code);
    
    // 3. Show current seat info
    $('#currentSeat').text(booking.seat_number);
    
    // 4. Open modal
    $('#relocateModal').modal('show');
});

// 5. User fills form and clicks "Relocate Booking"
$('#btnRelocateBooking').on('click', function() {
    const bookingId = $('#relocateBookingId').val();
    const data = {
        new_table_number: $('#newTableNumber').val(),
        new_row_number: $('#newRowNumber').val(),
        new_column_number: $('#newColumnNumber').val(),
        new_assign_number: $('#newSeatNumber').val(),
        reason: $('#relocationReason').val(),
        change_type: $('input[name="changeType"]:checked').val(),
        admin_confirmation: $('#confirmRelocation').is(':checked')
    };
    
    // 6. Call API (Line 796+)
    TempleAPI.post(`/special-occasion-bookings/${bookingId}/relocate`, data)
        .done(function(response) {
            // 7. Success - refresh table
            TempleCore.showToast('Booking relocated successfully', 'success');
            loadBookings(); // Reload booking list
            $('#relocateModal').modal('hide');
        })
        .fail(function(error) {
            TempleCore.showToast('Relocation failed', 'error');
        });
});
```

---

### **FLOW 4: View Relocation Report**

**File:** `temple2/js/pages/special-occasions/relocation-report.js`

```javascript
// Step 1: Navigate to Relocation Report
URL: /temple1/special-occasions/relocation-report

// Step 2: Report Page Layout
┌─────────────────────────────────────────────────────────────────┐
│ 🕐 Relocation Log Report                          [🔄 Refresh] │
│ ─────────────────────────────────────────────────────────────── │
│                                                                 │
│ 🔽 Filter Options                        [Clear Filters]       │
│ ┌─────────────────────────────────────────────────────────────┐ │
│ │ Event:      [All Events ▼]                                 │ │
│ │ Start Date: [2025-12-13]  End Date: [2026-01-12]          │ │
│ │ Changed By: [All Admins ▼]  Action: [All Actions ▼]       │ │
│ │ Booking #:  [Search...]                          [🔍]      │ │
│ │                                                             │ │
│ │ [📄 Export PDF]  [📊 Export Excel]                         │ │
│ └─────────────────────────────────────────────────────────────┘ │
│                                                                 │
│ 📋 Relocation Records                           1 record       │
│ ┌─────────────────────────────────────────────────────────────┐ │
│ │ Date/Time  │ Event │ Booking# │ Old → New │ Action │ By   │ │
│ ├────────────┼───────┼──────────┼───────────┼────────┼──────┤ │
│ │ 07/01/2026 │ Kosa  │ TEBD001  │ A1 → A2   │RELOCATE│admin │ │
│ │ 16:38      │ nda   │          │           │        │      │ │
│ └─────────────────────────────────────────────────────────────┘ │
│                                                                 │
│ Showing 1 to 1 of 1 records                                    │
└─────────────────────────────────────────────────────────────────┘

// Code Locations:
Line 78:  init() - Page initialization
Line 554: loadOccasions() - Load event dropdown
Line 578: loadAdmins() - Load admin dropdown (FIXED TODAY)
Line 603: loadRelocationLog() - Load report data
Line 772: exportToPDF() - PDF export (FIXED TODAY)
Line 858: exportToExcel() - Excel export (FIXED TODAY)
```

**Function Flow:**
```javascript
// 1. Page loads
SpecialOccasionsRelocationReportPage.init();
    ↓
// 2. Initialize date pickers (Line 536)
initDatePickers(); // Sets default 30-day range
    ↓
// 3. Load filter dropdowns
loadOccasions();  // Line 554 - Event dropdown
loadAdmins();     // Line 578 - Admin dropdown (FIXED)
    ↓
// 4. Load report data (Line 603)
loadRelocationLog();
    ↓
API Call: GET /api/v1/special-occasion-bookings/relocation-log
    ↓
// 5. Display in DataTable (Line 655)
initDataTable();
    ↓
// 6. User clicks "Export PDF" (Line 772)
exportToPDF();
    ↓
API Call: GET /api/v1/reports/relocation-report?format=pdf
    ↓
// 7. Download PDF file ✅
```

---

### **FLOW 5: Generate & Verify QR Code (NEW!)**

**Files:** Frontend (to be implemented) + Backend (ready)

```javascript
// STEP 1: Generate QR Code for Booking
// ─────────────────────────────────────────────────────────────

// Option A: Display QR on Receipt
async function showBookingReceipt(bookingId) {
    // Get QR code as base64
    const qrResponse = await TempleAPI.get(
        `/qr/booking/${bookingId}?format=base64`
    );
    
    // Display receipt with QR code
    const receiptHTML = `
        <div class="receipt">
            <h2>Booking Receipt</h2>
            <p>Booking #: ${booking.booking_number}</p>
            <p>Event: ${booking.event_name}</p>
            <p>Seat: ${booking.seat_number}</p>
            
            <!-- QR Code -->
            <div class="qr-code">
                <img src="${qrResponse.data.qr_code}" 
                     alt="Booking QR Code" 
                     style="width: 200px; height: 200px;" />
                <p>Scan to verify booking</p>
            </div>
        </div>
    `;
    
    $('#receiptContainer').html(receiptHTML);
}

// Option B: Download QR as Image
async function downloadQR(bookingId) {
    // Get QR as PNG
    const response = await fetch(
        `${TempleAPI.getBaseUrl()}/qr/booking/${bookingId}?format=png&size=400`,
        {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('access_token')}`,
                'X-Temple-ID': 'temple1'
            }
        }
    );
    
    const blob = await response.blob();
    const url = window.URL.createObjectURL(blob);
    
    // Trigger download
    const a = document.createElement('a');
    a.href = url;
    a.download = `booking_${bookingId}_qr.png`;
    a.click();
}


// STEP 2: Scan & Verify QR Code
// ─────────────────────────────────────────────────────────────

// Using a QR scanner library (e.g., html5-qrcode)
async function scanQRCode() {
    // Initialize scanner
    const html5QrCode = new Html5Qrcode("qr-reader");
    
    // Start scanning
    html5QrCode.start(
        { facingMode: "environment" }, // Use back camera
        {
            fps: 10,
            qrbox: { width: 250, height: 250 }
        },
        async (decodedText, decodedResult) => {
            // Stop scanner
            html5QrCode.stop();
            
            // Verify the QR code
            await verifyQRCode(decodedText);
        }
    );
}

async function verifyQRCode(qrData) {
    try {
        const response = await TempleAPI.post('/qr/verify', {
            qr_data: qrData
        });
        
        if (response.success) {
            const booking = response.data;
            
            // Display verification result
            showVerificationResult({
                bookingNumber: booking.booking_number,
                status: booking.booking_status,
                devotee: booking.devotee.display_name,
                event: booking.event.event_name,
                currentSeat: booking.current_seat.location,
                lastUpdated: booking.last_updated
            });
        }
    } catch (error) {
        showError('Invalid or expired QR code');
    }
}

function showVerificationResult(data) {
    const resultHTML = `
        <div class="verification-result success">
            <h3>✅ Valid Booking</h3>
            <table>
                <tr><th>Booking #:</th><td>${data.bookingNumber}</td></tr>
                <tr><th>Status:</th><td>${data.status}</td></tr>
                <tr><th>Devotee:</th><td>${data.devotee}</td></tr>
                <tr><th>Event:</th><td>${data.event}</td></tr>
                <tr><th>Current Seat:</th><td>${data.currentSeat}</td></tr>
                <tr><th>Last Updated:</th><td>${data.lastUpdated}</td></tr>
            </table>
        </div>
    `;
    
    $('#verificationResult').html(resultHTML);
}
```

---

## 🎯 Quick Access URLs

### **Production URLs**
```
Event Master (Enable Relocation):
https://temple2.chinesetemplesystems.xyz/temple1/special-occasions/master

Booking List (Relocate Seats):
https://temple2.chinesetemplesystems.xyz/temple1/special-occasions/index

Relocation Report:
https://temple2.chinesetemplesystems.xyz/temple1/special-occasions/relocation-report
```

### **API Endpoints**
```
# Relocation
POST /api/v1/special-occasion-bookings/{id}/relocate
POST /api/v1/special-occasion-bookings/swap

# Reports
GET  /api/v1/reports/relocation-report?format=pdf
GET  /api/v1/reports/relocation-stats

# QR Codes (NEW!)
GET  /api/v1/qr/booking/{id}?format=svg
POST /api/v1/qr/verify
```

---

## 🔍 How to Debug/Test

### **1. Check if Relocation is Enabled**
```javascript
// In browser console on booking list page:
console.log('Relocation enabled:', 
    $('.btn-relocate').length > 0 ? 'Yes' : 'No'
);
```

### **2. Test API Directly**
```javascript
// Get booking with relocation info
TempleAPI.get('/special-occasion-bookings')
    .done(response => {
        console.log('Bookings:', response.data);
        console.log('First booking relocation enabled:', 
            response.data[0]?.enable_relocation
        );
    });
```

### **3. Test QR Generation**
```javascript
// Generate QR for a booking
const bookingId = 'YOUR_BOOKING_ID';
window.open(
    `${TempleAPI.getBaseUrl()}/qr/booking/${bookingId}?format=svg`,
    '_blank'
);
```

---

## 📱 Mobile/Responsive Behavior

All pages are responsive and work on:
- ✅ Desktop (1920x1080)
- ✅ Tablet (768x1024)
- ✅ Mobile (375x667)

QR scanner will request camera permission on mobile devices.

---

## 🎨 UI Components Used

### **Bootstrap Components**
- Modals (relocation dialog)
- DataTables (report listing)
- Dropdowns (filters)
- Buttons (actions)
- Forms (input fields)

### **Custom Components**
- TempleAPI (API wrapper)
- TempleCore (utilities)
- Toast notifications
- Loading spinners

---

## 📝 Summary

**To see the complete flow:**

1. **Enable Relocation**: Go to Event Master → Edit event → Check "Enable Relocation"
2. **Relocate Booking**: Go to Booking List → Click 🔄 icon → Fill form → Submit
3. **View Report**: Go to Relocation Report → Apply filters → Export PDF/Excel
4. **Generate QR**: Call API `/qr/booking/{id}` (frontend integration pending)
5. **Verify QR**: Scan QR → Call API `/qr/verify` → See live booking data

**All backend APIs are ready and working!** ✅  
**Frontend QR integration is the next step** ⏭️

---

*Need help with a specific flow? Let me know!* 🚀

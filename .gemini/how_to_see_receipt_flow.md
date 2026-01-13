# 🎯 How to See Your Automatic Receipt Update Flow

## 📍 **You Are Here**
Looking at your screenshot, you're on the **Booking List** page:
```
URL: temple2.chinesetemplesystems.xyz/temple1/special-occasions
```

---

## 🔄 **Complete Flow - Step by Step**

### **STEP 1: View Current Booking**
**What you see now:**

```
┌─────────────────────────────────────────────────────────────┐
│ Bookings List                                               │
├─────────────────────────────────────────────────────────────┤
│ Booking Code: TEBD2025122900000001                         │
│ Date: 30/12/2025                                            │
│ Devotee: Prithivi                                           │
│ Event: Hungry Ghost Festival                                │
│ Actions: [👁️] [🔄] [📄]                                    │
│          View  Relocate Receipt                             │
└─────────────────────────────────────────────────────────────┘
```

**Current seat:** (Check by clicking 👁️ View icon)

---

### **STEP 2: Click Relocation Icon** 🔄

**Action:** Click the 🔄 (relocate) icon for any booking

**What happens:**
```
┌─────────────────────────────────────────────────────────────┐
│ 🔄 Relocate Booking                                         │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│ Current Information:                                        │
│  Booking: TEBD2025122900000001                             │
│  Current Seat: A1 (Table 1, R1C1)                          │
│                                                             │
│ New Location:                                               │
│  Table Number:    [Table 2 ▼]                              │
│  Row Number:      [3        ]                              │
│  Column Number:   [4        ]                              │
│  Seat Number:     [B12      ]                              │
│                                                             │
│ Reason for Change: [Required]                              │
│  [Devotee requested different location]                    │
│                                                             │
│ Change Type:                                                │
│  ⦿ Manual Relocation                                       │
│  ○ Forced (Override existing)                              │
│                                                             │
│ ☑️ I confirm this relocation                               │
│                                                             │
│ [Cancel]  [Relocate Booking]                               │
└─────────────────────────────────────────────────────────────┘
```

---

### **STEP 3: Fill Relocation Form**

**Fill in:**
1. **New Table:** Select from dropdown (e.g., "Table 2")
2. **Row Number:** Enter number (e.g., 3)
3. **Column Number:** Enter number (e.g., 4)
4. **Seat Number:** Enter seat (e.g., "B12")
5. **Reason:** Type reason (e.g., "Devotee requested")
6. **Check:** ✅ "I confirm this relocation"
7. **Click:** "Relocate Booking" button

---

### **STEP 4: Backend Magic Happens** ✨

**Behind the scenes (automatic):**

```
┌─────────────────────────────────────────────────────────────┐
│ BACKEND PROCESSING                                          │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│ ✅ Step 1: Validate new seat availability                  │
│ ✅ Step 2: Update booking_meta:                            │
│    - seat_number = "B12"                                    │
│    - table_number = "Table 2"                               │
│    - row_number = 3                                         │
│    - column_number = 4                                      │
│                                                             │
│ ✅ Step 3: Create relocation log                           │
│    - Old seat: A1                                           │
│    - New seat: B12                                          │
│    - Changed by: Your Admin Name                            │
│    - Reason: Devotee requested                              │
│                                                             │
│ ✅ Step 4: Update booking timestamp ← NEW!                 │
│    - booking.updated_at = NOW                               │
│                                                             │
│ ✅ Step 5: Generate QR code ← NEW!                         │
│    - Encrypted booking reference                            │
│    - Base64 encoded                                         │
│                                                             │
│ ✅ Step 6: Prepare seat data ← NEW!                        │
│    {                                                        │
│      "table_number": "Table 2",                             │
│      "row_number": 3,                                       │
│      "column_number": 4,                                    │
│      "seat_number": "B12",                                  │
│      "last_updated": "2026-01-12 20:08:45",                │
│      "updated_by": "Your Admin Name",                       │
│      "relocated": true                                      │
│    }                                                        │
│                                                             │
│ ✅ Step 7: Store in booking_meta ← NEW!                    │
│    - seat_assignment_data (JSON)                            │
│    - qr_code_base64 (text)                                  │
│                                                             │
│ ✅ Step 8: Commit transaction                              │
│                                                             │
│ ✅ SUCCESS! Receipt data auto-updated!                     │
└─────────────────────────────────────────────────────────────┘
```

---

### **STEP 5: See Success Message**

**What you see:**
```
┌─────────────────────────────────────────────────────────────┐
│ ✅ Success!                                                 │
│                                                             │
│ Booking relocated successfully                              │
│                                                             │
│ Old Seat: A1 (Table 1)                                      │
│ New Seat: B12 (Table 2)                                     │
│                                                             │
│ [OK]                                                        │
└─────────────────────────────────────────────────────────────┘
```

**The booking list refreshes automatically**

---

### **STEP 6: View Updated Receipt** 📄

**Action:** Click the 📄 (receipt) icon for the same booking

**What you'll see in the PDF:**

```
┌─────────────────────────────────────────────────────────────┐
│                    BOOKING RECEIPT                          │
│                                                             │
│ Booking Number: TEBD2025122900000001                       │
│ Date: 30/12/2025                                            │
│ Devotee: Prithivi                                           │
│                                                             │
├─────────────────────────────────────────────────────────────┤
│ 📍 Seat Assignment [RELOCATED] ← NEW BADGE!                │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│ Table: Table 2                                              │
│ Position: Row 3, Column 4                                   │
│ Seat Number: B12  ← UPDATED!                               │
│                                                             │
│ ⏱️ Last Updated: 12 January 2026, 08:08 PM                │
│    by Your Admin Name                                       │
│                                                             │
├─────────────────────────────────────────────────────────────┤
│ 📱 Scan to Verify Booking ← NEW SECTION!                   │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│              [QR CODE IMAGE]                                │
│                                                             │
│ Scan this QR code to view current booking                  │
│ details and seat assignment.                                │
│ The QR code always shows the latest                         │
│ information, even after relocations.                        │
│                                                             │
├─────────────────────────────────────────────────────────────┤
│ Booking Items                                               │
│ ...                                                         │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│ Generated on: 12 January 2026, 08:00 PM                    │
│ Receipt last updated: 12 January 2026, 08:08 PM ← NEW!    │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

---

### **STEP 7: Scan QR Code** 📱

**Action:** Use your phone to scan the QR code from the receipt

**Option 1: Use QR Scanner Page**
1. Go to: `temple2.chinesetemplesystems.xyz/temple1/qr-scanner.html`
2. Click "Start Scanner"
3. Point camera at QR code
4. See live booking data!

**Option 2: Use any QR scanner app**
1. Open QR scanner on phone
2. Scan the QR code
3. Copy the encrypted data
4. Go to QR Scanner page
5. Paste in "Manual Verification"
6. Click "Verify"

**What you'll see:**
```
┌─────────────────────────────────────────────────────────────┐
│ ✅ Valid Booking                                            │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│ Booking Number:  TEBD2025122900000001                      │
│ Status:          ✅ Confirmed                               │
│ Devotee:         Prithivi                                   │
│ Event:           Hungry Ghost Festival                      │
│ Current Seat:    Table: Table 2 | R3C4 | B12 ← LIVE DATA! │
│ Last Updated:    12/01/2026 20:08 PM                       │
│ Verified At:     12/01/2026 20:10 PM                       │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

**Notice:** The QR shows the NEW seat (B12), not the old seat (A1)!

---

## 🎬 **Quick Test Flow**

### **5-Minute Test:**

1. **Pick a booking** from your list (e.g., TEBD2025122900000001)

2. **Note current seat** (click 👁️ view icon)

3. **Click 🔄 relocate icon**

4. **Fill form:**
   - New Table: Table 2
   - Row: 3
   - Column: 4
   - Seat: B12
   - Reason: "Testing automatic receipt update"
   - ✅ Confirm

5. **Click "Relocate Booking"**

6. **Wait for success message**

7. **Click 📄 receipt icon**

8. **Check PDF for:**
   - ✅ "RELOCATED" badge
   - ✅ New seat: B12
   - ✅ Last updated timestamp
   - ✅ Your admin name
   - ✅ QR code image
   - ✅ "Receipt last updated" in footer

9. **Scan QR code** (optional)
   - Should show seat B12
   - Should show latest timestamp

10. **Done!** ✅

---

## 📊 **What to Look For**

### **In the Receipt PDF:**

✅ **Seat Assignment Section** (new!)
- Yellow/orange background
- Shows table, row, column, seat
- "RELOCATED" badge
- Last updated timestamp
- Admin name

✅ **QR Code Section** (new!)
- Centered QR code image
- Scan instructions
- Dashed border

✅ **Footer Timestamp** (new!)
- "Receipt last updated: ..."
- Different from "Generated on"

---

## 🔍 **Verification Checklist**

After relocating a booking, verify:

- [ ] Booking list shows new seat
- [ ] Receipt shows "RELOCATED" badge
- [ ] Receipt shows new seat number
- [ ] Receipt shows last updated time
- [ ] Receipt shows your admin name
- [ ] QR code appears in receipt
- [ ] QR code scans successfully
- [ ] Scanned QR shows new seat (not old)
- [ ] Footer shows "Receipt last updated"
- [ ] Timestamp matches relocation time

---

## 🎯 **Where to See Each Part**

| Feature | Where to See It |
|---------|-----------------|
| **Relocation Form** | Click 🔄 icon in booking list |
| **Updated Seat** | Receipt PDF, seat section |
| **RELOCATED Badge** | Receipt PDF, seat header |
| **Last Updated Time** | Receipt PDF, seat section & footer |
| **Admin Name** | Receipt PDF, seat section |
| **QR Code** | Receipt PDF, QR section |
| **Live Seat Data** | Scan QR → Verification page |

---

## 🚀 **Try It Now!**

**From your current page:**

1. Click 🔄 on the first booking (TEBD2025122900000001)
2. Change seat to something different
3. Submit
4. Click 📄 to view receipt
5. See all the new features! ✨

---

## 📱 **Mobile View**

The flow works the same on mobile:
- Relocation form is responsive
- Receipt PDF displays correctly
- QR scanner works with phone camera

---

## 💡 **Pro Tips**

1. **Test with different seats** to see updates
2. **Relocate same booking twice** to see timestamp change
3. **Compare old vs new receipts** to see differences
4. **Scan QR after each relocation** to verify live data
5. **Check relocation report** to see history

---

## 🎉 **Summary**

**The flow is:**
```
Relocate → Auto-update → View Receipt → Scan QR → Verify Live Data
```

**Everything happens automatically!**
- No manual receipt regeneration
- No manual QR code creation
- No manual data entry

**Just relocate and the receipt updates itself!** ✨

---

**Ready to test? Start from your current page and click any 🔄 icon!** 🚀

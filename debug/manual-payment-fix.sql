-- Emergency fix for booking payment status
-- Replace BOOKING_ID and PAYMENT_AMOUNT with actual values

-- Example: Update booking #1000 with a successful payment of RM 1000
UPDATE bookings 
SET 
    paid_amount = 1000.00,
    payment_status = 'paid'
WHERE id = 1000;

-- Add the payment record
INSERT INTO payments (booking_id, amount, payment_method, status, payment_date, transaction_id, notes)
VALUES (1000, 1000.00, 'toyyibpay', 'completed', NOW(), 'MANUAL_FIX_001', 'Manual payment status fix after successful ToyyibPay payment');

-- Check the result
SELECT 
    id,
    total_amount,
    paid_amount,
    (total_amount - paid_amount) as remaining,
    payment_status,
    booking_status
FROM bookings 
WHERE id = 1000;
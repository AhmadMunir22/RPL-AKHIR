/**
 * PadelBook Payments Manager
 * Handles wallet checkout forms and launches Midtrans Snap.js popups.
 */

window.PadelBookPayment = {
    async payWithWallet(bookingId, amount) {
        try {
            const response = await axios.post('/booking/pay-wallet', {
                booking_id: bookingId,
                pay_amount: amount
            });
            return response.data;
        } catch (error) {
            console.error('Wallet payment error:', error);
            throw error.response?.data || new Error('Pembayaran wallet gagal.');
        }
    },

    async getMidtransToken(bookingId, amount) {
        try {
            const response = await axios.post('/booking/pay-midtrans', {
                booking_id: bookingId,
                pay_amount: amount
            });
            return response.data.token;
        } catch (error) {
            console.error('Midtrans token error:', error);
            throw error.response?.data || new Error('Gagal mendapatkan token Midtrans.');
        }
    },

    launchMidtransSnap(token, callbacks) {
        if (!window.snap) {
            console.error('Midtrans Snap.js is not loaded.');
            return;
        }

        window.snap.pay(token, {
            onSuccess: (result) => {
                if (callbacks.onSuccess) callbacks.onSuccess(result);
            },
            onPending: (result) => {
                if (callbacks.onPending) callbacks.onPending(result);
            },
            onError: (result) => {
                if (callbacks.onError) callbacks.onError(result);
            },
            onClose: () => {
                if (callbacks.onClose) callbacks.onClose();
            }
        });
    }
};

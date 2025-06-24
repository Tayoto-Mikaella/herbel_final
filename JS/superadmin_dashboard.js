document.addEventListener("DOMContentLoaded", function () {
    const addGuestModal = document.getElementById("addGuestModal");
    const checkOutModal = document.getElementById("checkOutConfirmModal");
    const editModal = document.getElementById("editReservationModal");
    const cancelModal = document.getElementById("cancelReservationModal");

    const openModal = (modal) => { if (modal) modal.style.display = 'flex'; };
    const closeModal = (modal) => { if (modal) modal.style.display = 'none'; };

    document.querySelectorAll('.modal-overlay').forEach(modal => {
        const closeButton = modal.querySelector('.modal-close-btn, [data-close]');
        if (closeButton) {
            closeButton.addEventListener('click', () => closeModal(modal));
        }
    });

    window.addEventListener("click", function (event) {
        if (event.target.classList.contains('modal-overlay')) {
            closeModal(event.target);
        }
    });

    const flatpickrConfig = {
        enableTime: true,
        dateFormat: "Y-m-d H:i",
        altInput: true,
        altFormat: "m/d/Y h:i K",
        minDate: "today",
        time_24hr: false,
        minuteIncrement: 15,
    };
    
    const fpCheckInInstance = flatpickr("#check_in_date_modal", { ...flatpickrConfig,
        onChange: function (selectedDates) {
            if (fpCheckOutInstance) {
                fpCheckOutInstance.set("minDate", selectedDates[0] || "today");
            }
            triggerAllCalculations();
        },
    });

    const fpCheckOutInstance = flatpickr("#check_out_date_modal", { ...flatpickrConfig,
        onChange: function () { triggerAllCalculations(); },
    });
    
    const fpEditCheckIn = flatpickr("#edit-checkin-date", {...flatpickrConfig});
    const fpEditCheckOut = flatpickr("#edit-checkout-date", {...flatpickrConfig});

    document.getElementById("reservations-table-body")?.addEventListener("click", function (e) {
        const checkInBtn = e.target.closest(".check-in-btn");
        const checkOutBtn = e.target.closest(".check-out-btn");
        const editBtn = e.target.closest(".edit-reservation-btn");
        const cancelBtn = e.target.closest(".cancel-reservation-btn");

        if (checkInBtn) handleStatusUpdate(checkInBtn, 'Checked-In');
        if (checkOutBtn) handleCheckOut(checkOutBtn);
        if (editBtn) handleEdit(editBtn);
        if (cancelBtn) handleCancel(cancelBtn);
    });
    
    document.getElementById("addGuestBtn")?.addEventListener("click", function () {
        openModal(addGuestModal);
        resetFormAndCalculations();
    });

    async function fetchWithEnhancedErrorHandling(url, options) {
        try {
            const response = await fetch(url, options);
            if (!response.ok) {
                const errorText = await response.text();
                throw new Error(`HTTP error! status: ${response.status}, message: ${errorText}`);
            }
            const data = await response.json();
            return data;
        } catch (error) {
            console.error('Fetch Error:', error);
            alert('A server error occurred. Please check the console for details and try again.');
            return null;
        }
    }

    async function handleStatusUpdate(button, newStatus) {
        const reservationId = button.dataset.id;
        button.disabled = true;
        const data = await fetchWithEnhancedErrorHandling("update_status.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ reservation_id: reservationId, status: newStatus }),
        });
        
        if (data && data.success) {
            location.reload();
        } else {
            button.disabled = false;
        }
    }

    function handleCheckOut(button) {
        const guestName = button.closest("tr").dataset.guestName;
        const confirmText = checkOutModal.querySelector("#check-out-confirm-text");
        confirmText.textContent = `Are you sure you want to check out '${guestName}'?`;
        openModal(checkOutModal);
        const confirmBtn = document.getElementById("confirmCheckOut");
        const newConfirmBtn = confirmBtn.cloneNode(true);
        confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);
        newConfirmBtn.addEventListener("click", () => {
            handleStatusUpdate(button, 'Checked-Out');
            closeModal(checkOutModal);
        });
    }

    async function handleEdit(button) {
        const resId = button.dataset.id;
        const result = await fetchWithEnhancedErrorHandling(`get_reservation.php?id=${resId}`);
        
        if (result && result.success && result.data) {
            const res = result.data;
            document.getElementById('edit-reservation-id').value = res.Reservation_ID;
            document.getElementById('edit-guest-id').value = res.Guest_ID;
            document.getElementById('edit-first-name').value = res.First_Name;
            document.getElementById('edit-last-name').value = res.Last_Name;
            fpEditCheckIn.setDate(res.Check_in_Date, true);
            fpEditCheckOut.setDate(res.Check_out_Date, true);
            document.getElementById('edit-status').value = res.Status;
            openModal(editModal);
        }
    }
    
    function handleCancel(button) {
        const guestName = button.closest('tr').dataset.guestName;
        const confirmText = cancelModal.querySelector('#cancel-res-confirm-text');
        confirmText.textContent = `Do you really want to cancel the reservation for '${guestName}'? This will move it to history.`;
        openModal(cancelModal);
        const confirmBtn = document.getElementById('confirmResCancel');
        const newConfirmBtn = confirmBtn.cloneNode(true);
        confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);
        newConfirmBtn.addEventListener('click', () => {
            handleStatusUpdate(button, 'Cancelled');
            closeModal(cancelModal);
        });
    }
    
    const roomTypeSelect = document.getElementById("room_type_modal");
    const roomSelect = document.getElementById("room_id_modal");
    const roomCapacityDisplayValue = document.getElementById("room_capacity_display_value");
    const additionalPersonsInput = document.getElementById("additional_persons_modal");
    
    function resetFormAndCalculations() {
        document.getElementById("reservationFormModal")?.reset();
        roomSelect.innerHTML = '<option value="">-- Select Room Type First --</option>';
        roomSelect.disabled = true;
        roomCapacityDisplayValue.textContent = "--";
        triggerAllCalculations();
        fpCheckInInstance.clear();
        fpCheckOutInstance.clear();
    }

    roomTypeSelect?.addEventListener("change", function () {
        const selectedId = this.value;
        populateRoomsByType(selectedId);
        updateRoomCapacityDisplay(selectedId);
        triggerAllCalculations();
    });

    additionalPersonsInput?.addEventListener("input", triggerAllCalculations);

    function populateRoomsByType(roomTypeId) {
        roomSelect.innerHTML = "";
        if (!roomTypeId) {
            roomSelect.innerHTML = '<option value="">-- Select Room Type First --</option>';
            roomSelect.disabled = true;
            return;
        }
        const availableRooms = SCRIPT_ALL_ROOMS_DATA.filter(r => r.Room_Type_ID == roomTypeId && r.Status === "Available");
        if (availableRooms.length > 0) {
            roomSelect.disabled = false;
            roomSelect.innerHTML = '<option value="">-- Select a Room --</option>';
            availableRooms.forEach(room => {
                const option = document.createElement("option");
                option.value = room.Room_ID;
                option.textContent = room.Room_No;
                roomSelect.appendChild(option);
            });
        } else {
            roomSelect.innerHTML = '<option value="">-- No Rooms Available --</option>';
            roomSelect.disabled = true;
        }
    }

    function updateRoomCapacityDisplay(roomTypeId) {
        let capacity = "--";
        if (roomTypeId) {
            const roomType = SCRIPT_ROOM_TYPES_DATA.find(rt => rt.Room_Type_ID == roomTypeId);
            if (roomType) capacity = roomType.Capacity;
        }
        roomCapacityDisplayValue.textContent = capacity + (capacity !== "--" ? " pax" : "");
    }
    
    document.querySelectorAll(".duration-buttons button").forEach(button => {
        button.addEventListener("click", function () {
            const durationHours = parseInt(this.dataset.duration);
            const checkInValue = fpCheckInInstance.selectedDates[0];
            if (checkInValue && durationHours > 0) {
                const checkOutDate = new Date(checkInValue.getTime() + durationHours * 3600000);
                fpCheckOutInstance.setDate(checkOutDate, true);
            }
        });
    });

    function triggerAllCalculations() {
        const roomRateDisplay = document.getElementById("room_rate_modal");
        const additionalFeeDisplay = document.getElementById("additional_fee_display_modal");
        const actualAdditionalFeeInput = document.getElementById("additional_fee_actual_modal");
        const grandTotalDisplay = document.getElementById("grand_total_modal");
        const requiredPaymentDisplay = document.getElementById("required_payment_modal");

        let roomRate = 0, durationHours = 0;
        const checkIn = fpCheckInInstance.selectedDates[0];
        const checkOut = fpCheckOutInstance.selectedDates[0];
        const roomTypeId = roomTypeSelect.value;

        if (checkIn && checkOut && checkOut > checkIn && roomTypeId) {
            durationHours = (checkOut - checkIn) / 3600000;
            const roomType = SCRIPT_ROOM_TYPES_DATA.find(rt => rt.Room_Type_ID == roomTypeId);
            if (roomType) {
                if (durationHours <= 3) roomRate = parseFloat(roomType.Rate_3hr || 0);
                else if (durationHours <= 6) roomRate = parseFloat(roomType.Rate_6hr || 0);
                else if (durationHours <= 12) roomRate = parseFloat(roomType.Rate_12hr || 0);
                else {
                    const dailyRate = parseFloat(roomType.Rate_24hr || 0);
                    if (dailyRate > 0) {
                       const numDays = Math.ceil(durationHours / 24);
                       roomRate = dailyRate * numDays;
                    }
                }
            }
        }
        
        const additionalPersons = parseInt(additionalPersonsInput.value) || 0;
        const additionalFee = additionalPersons < 0 ? 0 : additionalPersons * 200;
        const grandTotal = roomRate + additionalFee;
        const requiredPayment = durationHours >= 48 ? grandTotal * 0.5 : grandTotal;

        if (roomRateDisplay) roomRateDisplay.value = roomRate.toFixed(2);
        if (additionalFeeDisplay) additionalFeeDisplay.value = additionalFee.toFixed(2);
        if (actualAdditionalFeeInput) actualAdditionalFeeInput.value = additionalFee.toFixed(2);
        if (grandTotalDisplay) grandTotalDisplay.value = grandTotal.toFixed(2);
        if (requiredPaymentDisplay) requiredPaymentDisplay.value = Math.max(0, requiredPayment).toFixed(2);
    }

    function checkUpcomingCheckouts() {
        const now = new Date();
        const tenMinutesFromNow = new Date(now.getTime() + 10 * 60000);
        document.querySelectorAll("#reservations-table-body tr").forEach(row => {
            const checkoutTimeStr = row.dataset.checkout;
            const status = row.dataset.status;
            if (status === "Checked-In" && checkoutTimeStr) {
                const checkoutTime = new Date(checkoutTimeStr);
                if (checkoutTime <= tenMinutesFromNow && checkoutTime > now) {
                    row.classList.add("due-soon");
                } else {
                    row.classList.remove("due-soon");
                }
            }
        });
    }

    checkUpcomingCheckouts();
    setInterval(checkUpcomingCheckouts, 60000);
});
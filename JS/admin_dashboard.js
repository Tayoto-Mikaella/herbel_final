document.addEventListener("DOMContentLoaded", function () {
  const modal = document.getElementById("addGuestModal");
  const addGuestBtn = document.getElementById("addGuestBtn");
  const closeModalBtn = document.getElementById("closeModalBtn");
  const checkOutModal = document.getElementById("checkOutConfirmModal");

  const roomTypeSelect = document.getElementById("room_type_modal");
  const roomSelect = document.getElementById("room_id_modal");
  const checkInInput = document.getElementById("check_in_date_modal");
  const checkOutInput = document.getElementById("check_out_date_modal");
  const durationButtons = document.querySelectorAll(".duration-buttons button");
  const roomCapacityDisplayValue = document.getElementById(
    "room_capacity_display_value"
  );
  const additionalPersonsInput = document.getElementById(
    "additional_persons_modal"
  );
  const additionalFeeDisplay = document.getElementById(
    "additional_fee_display_modal"
  );
  const actualAdditionalFeeInput = document.getElementById(
    "additional_fee_actual_modal"
  );
  const roomRateDisplay = document.getElementById("room_rate_modal");
  const grandTotalDisplay = document.getElementById("grand_total_modal");
  const requiredPaymentDisplay = document.getElementById(
    "required_payment_modal"
  );
  const roomTypesData =
    typeof SCRIPT_ROOM_TYPES_DATA !== "undefined" ? SCRIPT_ROOM_TYPES_DATA : [];
  const allRoomsData =
    typeof SCRIPT_ALL_ROOMS_DATA !== "undefined" ? SCRIPT_ALL_ROOMS_DATA : [];

  function requestNotificationPermission() {
    if (
      "Notification" in window &&
      Notification.permission !== "granted" &&
      Notification.permission !== "denied"
    ) {
      Notification.requestPermission().then((permission) => {
        if (permission === "granted") {
          new Notification("Herbel Apartelle", {
            body: "Notifications are enabled.",
            icon: "../logo.png",
          });
        }
      });
    }
  }

  function checkUpcomingCheckouts() {
    const now = new Date();
    const tenMinutesFromNow = new Date(now.getTime() + 10 * 60000);
    document.querySelectorAll("#reservations-table-body tr").forEach((row) => {
      const checkoutTimeStr = row.dataset.checkout;
      const status = row.dataset.status;
      const reservationId = row.id.replace("res-row-", "");
      if (status === "Checked-In" && checkoutTimeStr) {
        const checkoutTime = new Date(checkoutTimeStr);
        row.classList.remove("due-soon");
        if (checkoutTime <= tenMinutesFromNow && checkoutTime > now) {
          row.classList.add("due-soon");
          if (Notification.permission === "granted") {
            const notificationShown = sessionStorage.getItem(
              "notif_" + reservationId
            );
            if (!notificationShown) {
              const guestName = row.cells[0].textContent;
              new Notification("Upcoming Check-out", {
                body: `${guestName} is due for check-out in less than 10 minutes.`,
                icon: "../logo.png",
                tag: "checkout-" + reservationId,
              });
              sessionStorage.setItem("notif_" + reservationId, "true");
            }
          }
        }
      }
    });
  }

  function handleStatusUpdate(button) {
    const reservationId = button.dataset.id;
    const action = button.dataset.status;
    const newStatus = action === "check-in" ? "Checked-In" : "Checked-Out";

    button.disabled = true;
    button.textContent = "Updating...";

    fetch("update_status.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        reservation_id: reservationId,
        status: newStatus,
      }),
    })
      .then((response) => {
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
      })
      .then((data) => {
        if (data.success && data.reservation) {
          updateTableRow(reservationId, data.reservation);
        } else {
          throw new Error(data.message || "Unknown error occurred.");
        }
      })
      .catch((error) => {
        console.error("Error:", error);
        alert(`An error occurred: ${error.message}. Please try again.`);
        const originalText =
          action === "check-in"
            ? '<i class="fas fa-sign-in-alt"></i> Check-in'
            : '<i class="fas fa-sign-out-alt"></i> Check-out';
        button.innerHTML = originalText;
        button.disabled = false;
      });
  }

  function updateTableRow(reservationId, reservationData) {
    const row = document.getElementById(`res-row-${reservationId}`);
    if (!row) return;

    if (reservationData.status === "Checked-Out") {
      row.classList.add("fade-out");
      row.addEventListener("animationend", () => {
        row.remove();
      });
    } else {
      row.dataset.status = reservationData.status;
      const statusCell = row.querySelector(".status-cell");
      if (statusCell) {
        const statusClass = reservationData.status
          .toLowerCase()
          .replace(/ /g, "")
          .replace(/-/g, "");
        statusCell.innerHTML = `<span class="status-badge status-${statusClass}">${reservationData.status}</span>`;
      }

      const actionsCell = row.querySelector(".actions-cell");
      if (actionsCell) {
        if (reservationData.status === "Checked-In") {
          const checkOutButtonHTML = `<button class="action-btn check-out-btn" data-id="${reservationId}" data-status="check-out"><i class="fas fa-sign-out-alt"></i> Check-out</button>`;
          // This logic assumes superadmin view has extra buttons. A bit complex to merge here.
          // Simple replacement for now. A better approach would be to check if other buttons exist and preserve them.
          const superAdminButtons =
            actionsCell.querySelectorAll(".action-btn-icon");
          if (superAdminButtons.length > 0) {
            actionsCell.innerHTML = checkOutButtonHTML;
            superAdminButtons.forEach((btn) =>
              actionsCell.appendChild(btn.cloneNode(true))
            );
          } else {
            actionsCell.innerHTML = checkOutButtonHTML;
          }
        } else {
          actionsCell.innerHTML = `<span>--</span>`;
        }
      }
    }
  }

  document
    .getElementById("reservations-table-body")
    ?.addEventListener("click", function (e) {
      const checkInBtn = e.target.closest(".check-in-btn");
      const checkOutBtn = e.target.closest(".check-out-btn");

      if (checkInBtn) {
        handleStatusUpdate(checkInBtn);
      }

      if (checkOutBtn) {
        const guestName = checkOutBtn.closest("tr").dataset.guestName;
        const checkOutConfirmText = checkOutModal.querySelector(
          "#check-out-confirm-text"
        );
        checkOutConfirmText.textContent = `Are you sure you want to check out '${guestName}'?`;

        checkOutModal.style.display = "flex";

        const confirmBtn = document.getElementById("confirmCheckOut");
        const newConfirmBtn = confirmBtn.cloneNode(true);
        confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);

        newConfirmBtn.addEventListener("click", () => {
          handleStatusUpdate(checkOutBtn);
          checkOutModal.style.display = "none";
        });
      }
    });

  if (checkOutModal) {
    const cancelBtn = checkOutModal.querySelector("#cancelCheckOut");
    cancelBtn.addEventListener("click", () => {
      checkOutModal.style.display = "none";
    });
  }

  const flatpickrConfig = {
    enableTime: true,
    dateFormat: "Y-m-d H:i",
    altInput: true,
    altFormat: "m/d/Y h:i K",
    minDate: "today",
    time_24hr: false,
    minuteIncrement: 15,
  };
  let fpCheckInInstance, fpCheckOutInstance;
  if (checkInInput) {
    fpCheckInInstance = flatpickr(checkInInput, {
      ...flatpickrConfig,
      onChange: function (selectedDates, dateStr, instance) {
        if (fpCheckOutInstance) {
          if (
            fpCheckOutInstance.selectedDates.length > 0 &&
            selectedDates[0] >= fpCheckOutInstance.selectedDates[0]
          ) {
            fpCheckOutInstance.clear();
          }
          fpCheckOutInstance.set("minDate", selectedDates[0] || "today");
        }
        triggerAllCalculations();
      },
    });
  }
  if (checkOutInput) {
    fpCheckOutInstance = flatpickr(checkOutInput, {
      ...flatpickrConfig,
      onChange: function (selectedDates, dateStr, instance) {
        triggerAllCalculations();
      },
    });
  }
  if (addGuestBtn) {
    addGuestBtn.addEventListener("click", function () {
      if (modal) {
        modal.style.display = "flex";
        resetFormAndCalculations();
      }
    });
  }
  if (closeModalBtn) {
    closeModalBtn.addEventListener("click", function () {
      if (modal) {
        modal.style.display = "none";
      }
    });
  }
  if (modal) {
    window.addEventListener("click", function (event) {
      if (event.target == modal) {
        modal.style.display = "none";
      }
    });
  }
  function resetFormAndCalculations() {
    const form = document.getElementById("reservationFormModal");
    if (form) form.reset();
    if (roomTypeSelect) roomTypeSelect.value = "";
    if (roomSelect) {
      roomSelect.innerHTML =
        '<option value="">-- Select Room Type First --</option>';
      roomSelect.disabled = true;
    }
    if (roomCapacityDisplayValue) roomCapacityDisplayValue.textContent = "--";
    if (additionalPersonsInput) additionalPersonsInput.value = "0";
    if (additionalFeeDisplay) additionalFeeDisplay.value = "0.00";
    if (actualAdditionalFeeInput) actualAdditionalFeeInput.value = "0.00";
    if (roomRateDisplay) roomRateDisplay.value = "0.00";
    if (grandTotalDisplay) grandTotalDisplay.value = "0.00";
    if (requiredPaymentDisplay) requiredPaymentDisplay.value = "0.00";
    if (fpCheckInInstance) fpCheckInInstance.clear();
    if (fpCheckOutInstance) {
      fpCheckOutInstance.clear();
      fpCheckOutInstance.set("minDate", "today");
    }
    removeDurationButtonActiveState();
  }
  if (roomTypeSelect) {
    roomTypeSelect.addEventListener("change", function () {
      const selectedRoomTypeId = this.value;
      populateRoomsByType(selectedRoomTypeId);
      updateRoomCapacityDisplay(selectedRoomTypeId);
      triggerAllCalculations();
    });
  }
  function populateRoomsByType(roomTypeId) {
    if (!roomSelect) return;
    roomSelect.innerHTML = "";
    if (!roomTypeId) {
      roomSelect.innerHTML =
        '<option value="">-- Select Room Type First --</option>';
      roomSelect.disabled = true;
      return;
    }
    roomSelect.disabled = false;
    let foundRooms = false;
    const placeholderOption = document.createElement("option");
    placeholderOption.value = "";
    placeholderOption.textContent = "-- Select an Available Room --";
    roomSelect.appendChild(placeholderOption);
    const availableRoomsOfSelectedType = allRoomsData.filter(
      (room) => room.Room_Type_ID == roomTypeId && room.Status === "Available"
    );
    if (availableRoomsOfSelectedType.length > 0) {
      availableRoomsOfSelectedType.forEach((room) => {
        const option = document.createElement("option");
        option.value = room.Room_ID;
        option.textContent = room.Room_No;
        roomSelect.appendChild(option);
        foundRooms = true;
      });
    }
    if (!foundRooms) {
      const selectedRoomType = roomTypesData.find(
        (rt) => rt.Room_Type_ID == roomTypeId
      );
      const typeName = selectedRoomType
        ? selectedRoomType.Type_Name
        : "this type";
      roomSelect.innerHTML = `<option value="">-- No ${typeName} rooms available --</option>`;
      roomSelect.disabled = true;
    }
  }
  function updateRoomCapacityDisplay(roomTypeId) {
    if (!roomCapacityDisplayValue) return;
    let capacity = "--";
    if (roomTypeId) {
      const roomType = roomTypesData.find(
        (rt) => rt.Room_Type_ID == roomTypeId
      );
      if (roomType) {
        capacity = roomType.Capacity;
      }
    }
    roomCapacityDisplayValue.textContent =
      capacity + (capacity !== "--" ? " pax" : "");
    if (additionalPersonsInput) additionalPersonsInput.value = "0";
  }
  function removeDurationButtonActiveState() {
    durationButtons.forEach((btn) => btn.classList.remove("active"));
  }
  durationButtons.forEach((button) => {
    button.addEventListener("click", function () {
      removeDurationButtonActiveState();
      this.classList.add("active");
      const durationHours = parseInt(this.dataset.duration);
      const checkInValue = fpCheckInInstance.selectedDates[0];
      if (checkInValue && durationHours > 0) {
        const checkInDate = new Date(checkInValue);
        const checkOutDate = new Date(
          checkInDate.getTime() + durationHours * 60 * 60 * 1000
        );
        if (fpCheckOutInstance) fpCheckOutInstance.setDate(checkOutDate, true);
      } else if (!checkInValue) {
        alert("Please select a Check-in Date & Time first.");
        if (fpCheckInInstance) fpCheckInInstance.open();
      }
      triggerAllCalculations();
    });
  });
  if (additionalPersonsInput) {
    additionalPersonsInput.addEventListener(
      "input",
      updateAdditionalFeeAndGrandTotal
    );
  }
  if (checkInInput && fpCheckInInstance) {
    fpCheckInInstance.config.onChange.push(function (
      selectedDates,
      dateStr,
      instance
    ) {
      triggerAllCalculations();
    });
  }
  if (checkOutInput && fpCheckOutInstance) {
    fpCheckOutInstance.config.onChange.push(function (
      selectedDates,
      dateStr,
      instance
    ) {
      triggerAllCalculations();
    });
  }
  function updateAdditionalFeeAndGrandTotal() {
    const additionalPersons = parseInt(additionalPersonsInput.value) || 0;
    const feePerAdditionalPerson = 200;
    const calculatedAdditionalFee =
      additionalPersons < 0 ? 0 : additionalPersons * feePerAdditionalPerson;
    if (additionalFeeDisplay)
      additionalFeeDisplay.value = calculatedAdditionalFee.toFixed(2);
    if (actualAdditionalFeeInput)
      actualAdditionalFeeInput.value = calculatedAdditionalFee.toFixed(2);
    calculateRoomRateAndGrandTotal();
  }
  function calculateRoomRateAndGrandTotal() {
    let calculatedRoomRate = 0.0;
    let durationHours = 0;
    if (
      fpCheckInInstance &&
      fpCheckOutInstance &&
      fpCheckInInstance.selectedDates[0] &&
      fpCheckOutInstance.selectedDates[0] &&
      roomTypeSelect.value
    ) {
      const checkInTime = fpCheckInInstance.selectedDates[0].getTime();
      const checkOutTime = fpCheckOutInstance.selectedDates[0].getTime();
      if (checkOutTime > checkInTime) {
        const durationMillis = checkOutTime - checkInTime;
        durationHours = durationMillis / (1000 * 60 * 60);
        const selectedRoomTypeId = roomTypeSelect.value;
        const roomType = roomTypesData.find(
          (rt) => rt.Room_Type_ID == selectedRoomTypeId
        );
        if (roomType) {
          let rateFound = false;
          if (
            durationHours > 0 &&
            durationHours <= 3 &&
            roomType.Rate_3hr !== null &&
            roomType.Rate_3hr !== ""
          ) {
            calculatedRoomRate = parseFloat(roomType.Rate_3hr);
            rateFound = true;
          } else if (
            durationHours > 3 &&
            durationHours <= 6 &&
            roomType.Rate_6hr !== null &&
            roomType.Rate_6hr !== ""
          ) {
            calculatedRoomRate = parseFloat(roomType.Rate_6hr);
            rateFound = true;
          } else if (
            durationHours > 6 &&
            durationHours <= 12 &&
            roomType.Rate_12hr !== null &&
            roomType.Rate_12hr !== ""
          ) {
            calculatedRoomRate = parseFloat(roomType.Rate_12hr);
            rateFound = true;
          } else if (
            durationHours > 12 &&
            durationHours <= 24 &&
            roomType.Rate_24hr !== null &&
            roomType.Rate_24hr !== ""
          ) {
            calculatedRoomRate = parseFloat(roomType.Rate_24hr);
            rateFound = true;
          } else if (
            durationHours > 24 &&
            roomType.Rate_24hr !== null &&
            roomType.Rate_24hr !== ""
          ) {
            const numDays = Math.ceil(durationHours / 24);
            calculatedRoomRate = parseFloat(roomType.Rate_24hr) * numDays;
            rateFound = true;
          }
          if (!rateFound && durationHours > 0) {
            if (roomType.Rate_3hr !== null && roomType.Rate_3hr !== "")
              calculatedRoomRate = parseFloat(roomType.Rate_3hr);
            else if (roomType.Rate_6hr !== null && roomType.Rate_6hr !== "")
              calculatedRoomRate = parseFloat(roomType.Rate_6hr);
            else if (roomType.Rate_12hr !== null && roomType.Rate_12hr !== "")
              calculatedRoomRate = parseFloat(roomType.Rate_12hr);
            else if (roomType.Rate_24hr !== null && roomType.Rate_24hr !== "")
              calculatedRoomRate = parseFloat(roomType.Rate_24hr);
            else calculatedRoomRate = 0.0;
          }
        }
      } else {
        durationHours = 0;
      }
    } else {
      durationHours = 0;
    }
    if (roomRateDisplay) roomRateDisplay.value = calculatedRoomRate.toFixed(2);
    const additionalFee = parseFloat(actualAdditionalFeeInput.value) || 0;
    const grandTotal = calculatedRoomRate + additionalFee;
    if (grandTotalDisplay) grandTotalDisplay.value = grandTotal.toFixed(2);
    let requiredPayment = 0.0;
    if (grandTotal > 0) {
      if (durationHours >= 48) {
        requiredPayment = grandTotal * 0.5;
      } else {
        requiredPayment = grandTotal;
      }
    }
    if (requiredPaymentDisplay)
      requiredPaymentDisplay.value = requiredPayment.toFixed(2);
  }
  function triggerAllCalculations() {
    updateRoomCapacityDisplay(roomTypeSelect.value);
    updateAdditionalFeeAndGrandTotal();
  }

  requestNotificationPermission();
  checkUpcomingCheckouts();
  setInterval(checkUpcomingCheckouts, 60000);

  if (modal) {
    resetFormAndCalculations();
    modal.style.display = "none";
  }
});

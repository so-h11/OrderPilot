// js/cashier_dashboard.js

(function () {
  "use strict";

  const REFRESH_INTERVAL_MS = 3000;

  const els = {
    statSales: document.getElementById("statSales"),
    statPending: document.getElementById("statPending"),
    statCompleted: document.getElementById("statCompleted"),
    statCompletedCard: document.getElementById("statCompletedCard"),
    orderTable: document.getElementById("cashierOrderTable"),
    completedOrderTable: document.getElementById("completedOrderTable"),
    btnViewCompleted: document.getElementById("btnViewCompleted"),
    btnClearAllCompleted: document.getElementById("btnClearAllCompleted"),
    btnNewWalkIn: document.getElementById("btnNewWalkIn"),
  };

  let ordersCache = [];
  let completedOrdersCache = [];
  let posMenuItems = [];
  let posCart = [];
  let currentPOSCategory = 'All';

  let orderModal, completedModal, walkInPOSModal, posItemModal, posCheckoutModal;

  document.addEventListener("DOMContentLoaded", function () {
    if (document.getElementById("orderModal")) orderModal = new bootstrap.Modal(document.getElementById("orderModal"));
    if (document.getElementById("completedModal")) completedModal = new bootstrap.Modal(document.getElementById("completedModal"));
    if (document.getElementById("walkInPOSModal")) walkInPOSModal = new bootstrap.Modal(document.getElementById("walkInPOSModal"));
    if (document.getElementById("posItemModal")) posItemModal = new bootstrap.Modal(document.getElementById("posItemModal"));
    if (document.getElementById("posCheckoutModal")) posCheckoutModal = new bootstrap.Modal(document.getElementById("posCheckoutModal"));

    loadDashboard();
    setInterval(loadDashboard, REFRESH_INTERVAL_MS);

    if (els.btnViewCompleted) els.btnViewCompleted.addEventListener("click", openCompletedOrdersModal);
    if (els.statCompletedCard) els.statCompletedCard.addEventListener("click", openCompletedOrdersModal);
    if (els.btnClearAllCompleted) els.btnClearAllCompleted.addEventListener("click", clearAllCompletedOrders);

    if (els.btnNewWalkIn) {
      els.btnNewWalkIn.addEventListener("click", openWalkInPOS);
    }

    const posSearch = document.getElementById("posMenuSearch");
    if (posSearch) {
      posSearch.addEventListener("input", renderPOSMenu);
    }

    // Active Orders Event Delegation
    if (els.orderTable) {
      els.orderTable.addEventListener("click", function (e) {
        const row = e.target.closest("tr");
        const approveBtn = e.target.closest("[data-action='approve-pay']");
        const cancelBtn = e.target.closest("[data-action='cancel']");
        const detailsBtn = e.target.closest("[data-action='view-details']");

        if (approveBtn) {
          e.stopPropagation();
          approveAndPay(approveBtn.dataset.orderId, approveBtn);
        } else if (cancelBtn) {
          e.stopPropagation();
          cancelOrder(cancelBtn.dataset.orderId, cancelBtn);
        } else if (detailsBtn || row) {
          const orderId = approveBtn?.dataset.orderId || cancelBtn?.dataset.orderId || detailsBtn?.dataset.orderId || row?.dataset.orderId;
          if (orderId) openOrderModal(orderId, false);
        }
      });
    }

    // Completed Orders Event Delegation
    if (els.completedOrderTable) {
      els.completedOrderTable.addEventListener("click", function (e) {
        const row = e.target.closest("tr");
        const viewBtn = e.target.closest("[data-action='view-completed-receipt']");
        const deleteBtn = e.target.closest("[data-action='delete-completed']");

        if (viewBtn) {
          e.stopPropagation();
          openOrderModal(viewBtn.dataset.orderId, true);
        } else if (deleteBtn) {
          e.stopPropagation();
          deleteCompletedOrder(deleteBtn.dataset.orderId, deleteBtn);
        } else if (row) {
          const orderId = row.dataset.orderId;
          if (orderId) openOrderModal(orderId, true);
        }
      });
    }
  });

  function loadDashboard() {
    fetch("getorder.php")
      .then((res) => res.json())
      .then((data) => {
        if (data.success || data.status === 'success') {
          ordersCache = data.orders || data.data || [];
          renderStats(data.stats || { sales: 0, pending: 0, completed: 0 });
          renderOrderTable(ordersCache);
        }
      })
      .catch((err) => console.error("Cashier Dashboard Error:", err));
  }

  function renderStats(stats) {
    if (els.statSales) els.statSales.textContent = Number(stats.sales || 0).toFixed(2);
    if (els.statPending) els.statPending.textContent = stats.pending || 0;
    if (els.statCompleted) els.statCompleted.textContent = stats.completed || 0;
  }

  function renderOrderTable(orders) {
    if (!orders || !orders.length) {
      els.orderTable.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-muted">No pending customer orders right now.</td></tr>';
      return;
    }
    els.orderTable.innerHTML = orders.map(renderOrderRow).join("");
  }

  function renderOrderRow(order) {
    const isPaid = order.payment_status === "Paid";
    const isUnapproved = order.kitchen_status === "pending_approval" || !isPaid;
    const time = formatTime(order.order_time);

    let kitchenStatusLabel = order.kitchen_status === 'pending_approval' ? 'Pending Approval' : (order.kitchen_status === 'pending' ? 'In Queue' : order.kitchen_status);
    let statusClass = (order.kitchen_status === 'pending_approval' || !isPaid) ? 'status-preparing' : 'status-ready';

    let actionButtons = '';
    if (isUnapproved) {
      actionButtons = `
        <button type="button" class="btn btn-sm btn-success fw-bold me-1" data-action="approve-pay" data-order-id="${order.order_id}">
          <i class="fas fa-check me-1"></i> Approve & Pay
        </button>
        <button type="button" class="btn btn-sm btn-outline-danger fw-bold" data-action="cancel" data-order-id="${order.order_id}">
          <i class="fas fa-xmark"></i>
        </button>`;
    } else {
      actionButtons = `
        <button type="button" class="btn btn-sm btn-outline-primary fw-bold" data-action="view-details" data-order-id="${order.order_id}">
          <i class="fas fa-eye me-1"></i> View / Receipt
        </button>`;
    }

    return `
      <tr data-order-id="${order.order_id}" class="clickable-row" style="cursor: pointer;">
        <td data-label="Order ID"><strong>#${order.order_id}</strong></td>
        <td data-label="Time" class="op-time">${time}</td>
        <td data-label="Kitchen Status"><span class="badge-status ${statusClass}">${kitchenStatusLabel}</span></td>
        <td data-label="Payment"><span class="badge-payment ${isPaid ? "paid" : "text-danger fw-bold"}">${order.payment_status} (${order.payment_method})</span></td>
        <td data-label="Total" class="op-total">RM ${Number(order.total_amount).toFixed(2)}</td>
        <td data-label="Action" style="text-align: right;">${actionButtons}</td>
      </tr>`;
  }

  function openWalkInPOS() {
    posCart = [];
    updatePOSCartUI();
    fetchPOSMenu();
    if (walkInPOSModal) walkInPOSModal.show();
  }

  function fetchPOSMenu() {
    const grid = document.getElementById("posMenuGrid");
    grid.innerHTML = '<div class="col-12 text-center py-5 text-muted"><i class="fas fa-spinner fa-spin me-2"></i>Loading Menu...</div>';

    fetch("../api/menu_handler.php")
      .then((res) => res.json())
      .then((data) => {
        if (data.status === "success") {
          posMenuItems = data.data || [];
          renderPOSCategories();
          renderPOSMenu();
        } else {
          grid.innerHTML = '<div class="col-12 text-center py-5 text-danger">Failed to load menu.</div>';
        }
      })
      .catch((err) => {
        grid.innerHTML = '<div class="col-12 text-center py-5 text-danger">Error loading menu items.</div>';
      });
  }

  function renderPOSCategories() {
    const filterContainer = document.getElementById("posCategoryFilters");
    const categories = [...new Set(posMenuItems.map((i) => i.category_name))].filter(Boolean);

    filterContainer.innerHTML = `<button class="btn btn-sm btn-primary rounded-pill px-3 active" onclick="window.filterPOSMenu('All', this)">All</button>`;
    categories.forEach((cat) => {
      filterContainer.innerHTML += `<button class="btn btn-sm btn-outline-secondary rounded-pill px-3" onclick="window.filterPOSMenu('${escapeHtml(cat)}', this)">${escapeHtml(cat)}</button>`;
    });
  }

  window.filterPOSMenu = function (category, btnElement) {
    currentPOSCategory = category;
    const buttons = document.querySelectorAll("#posCategoryFilters button");
    buttons.forEach((b) => {
      b.classList.remove("btn-primary", "active");
      b.classList.add("btn-outline-secondary");
    });
    if (btnElement) {
      btnElement.classList.remove("btn-outline-secondary");
      btnElement.classList.add("btn-primary", "active");
    }
    renderPOSMenu();
  };

  function renderPOSMenu() {
    const grid = document.getElementById("posMenuGrid");
    const searchTerm = (document.getElementById("posMenuSearch")?.value || "").toLowerCase();
    grid.innerHTML = "";

    const filtered = posMenuItems.filter((item) => {
      if (item.is_available != 1) return false;
      if (currentPOSCategory !== "All" && item.category_name !== currentPOSCategory) return false;
      if (searchTerm && !item.item_name.toLowerCase().includes(searchTerm)) return false;
      return true;
    });

    if (!filtered.length) {
      grid.innerHTML = '<div class="col-12 text-center py-5 text-muted">No items available.</div>';
      return;
    }

    filtered.forEach((item) => {
      const rawImage = item.image_path || item.image;
      const imgSrc = rawImage ? "../" + rawImage : "https://via.placeholder.com/150?text=No+Image";

      grid.innerHTML += `
        <div class="col-sm-6 col-md-4 col-xl-3">
          <div class="card h-100 border-0 shadow-sm rounded-3 overflow-hidden" onclick="window.openPOSItemModal(${item.item_id})" style="cursor: pointer;">
            <img src="${imgSrc}" class="card-img-top" style="height: 120px; object-fit: cover;" alt="${escapeHtml(item.item_name)}">
            <div class="card-body p-2 d-flex flex-column justify-content-between">
              <h6 class="fw-bold mb-1 text-truncate">${escapeHtml(item.item_name)}</h6>
              <div class="d-flex justify-content-between align-items-center mt-2">
                <span class="fw-bold text-success">RM ${parseFloat(item.price).toFixed(2)}</span>
                <button type="button" class="btn btn-sm btn-primary rounded-circle"><i class="fas fa-plus"></i></button>
              </div>
            </div>
          </div>
        </div>`;
    });
  }

  window.openPOSItemModal = function (id) {
    const item = posMenuItems.find((i) => i.item_id == id);
    if (!item) return;

    document.getElementById("posModalItemId").value = item.item_id;
    document.getElementById("posModalItemName").textContent = item.item_name;
    document.getElementById("posModalItemPrice").textContent = parseFloat(item.price).toFixed(2);
    document.getElementById("posModalItemQty").value = "1";
    document.getElementById("posModalItemRemarks").value = "";

    if (posItemModal) posItemModal.show();
  };

  window.changePOSModalQty = function (change) {
    const qtyInput = document.getElementById("posModalItemQty");
    let current = parseInt(qtyInput.value) || 1;
    let newQty = current + change;
    if (newQty >= 1 && newQty <= 20) {
      qtyInput.value = newQty;
    }
  };

  window.confirmPOSAddToCart = function () {
    const id = document.getElementById("posModalItemId").value;
    const item = posMenuItems.find((i) => i.item_id == id);
    const qty = parseInt(document.getElementById("posModalItemQty").value) || 1;
    const remarks = document.getElementById("posModalItemRemarks").value.trim();

    const existingIndex = posCart.findIndex((c) => c.id == id && c.remarks === remarks);
    if (existingIndex > -1) {
      posCart[existingIndex].qty += qty;
    } else {
      posCart.push({
        id: item.item_id,
        name: item.item_name,
        price: parseFloat(item.price),
        qty: qty,
        remarks: remarks,
      });
    }

    if (posItemModal) posItemModal.hide();
    updatePOSCartUI();
  };

  function updatePOSCartUI() {
    const list = document.getElementById("posCartList");
    const totalEl = document.getElementById("posCartTotal");
    const checkoutBtn = document.getElementById("btnPOSCheckout");

    list.innerHTML = "";
    let total = 0;

    if (!posCart.length) {
      list.innerHTML = '<li class="list-group-item text-center text-muted py-4">Cart is empty</li>';
      totalEl.textContent = "0.00";
      checkoutBtn.disabled = true;
      return;
    }

    posCart.forEach((cItem, index) => {
      const subtotal = cItem.price * cItem.qty;
      total += subtotal;

      list.innerHTML += `
        <li class="list-group-item d-flex justify-content-between align-items-start py-2">
          <div style="flex: 1;">
            <strong class="d-block">${cItem.qty}x ${escapeHtml(cItem.name)}</strong>
            <small class="text-muted">RM ${cItem.price.toFixed(2)} each</small>
            ${cItem.remarks ? `<br><small class="text-danger">Note: ${escapeHtml(cItem.remarks)}</small>` : ""}
          </div>
          <div class="text-end">
            <span class="fw-bold d-block">RM ${subtotal.toFixed(2)}</span>
            <button class="btn btn-sm text-danger p-0 border-0" onclick="window.removePOSCartItem(${index})">
              <i class="fas fa-trash-can"></i>
            </button>
          </div>
        </li>`;
    });

    totalEl.textContent = total.toFixed(2);
    checkoutBtn.disabled = false;
  }

  window.removePOSCartItem = function (index) {
    posCart.splice(index, 1);
    updatePOSCartUI();
  };

  window.clearPOSCart = function () {
    posCart = [];
    updatePOSCartUI();
  };

  window.openPOSCheckout = function () {
    if (!posCart.length) return;

    const list = document.getElementById("posCheckoutSummaryList");
    const totalEl = document.getElementById("posCheckoutTotal");
    list.innerHTML = "";
    let total = 0;

    posCart.forEach((cItem) => {
      const subtotal = cItem.price * cItem.qty;
      total += subtotal;
      list.innerHTML += `
        <li class="list-group-item d-flex justify-content-between align-items-center">
          <div>
            <strong>${cItem.qty}x ${escapeHtml(cItem.name)}</strong>
            ${cItem.remarks ? `<br><small class="text-muted">Note: ${escapeHtml(cItem.remarks)}</small>` : ""}
          </div>
          <span class="fw-bold">RM ${subtotal.toFixed(2)}</span>
        </li>`;
    });

    totalEl.textContent = total.toFixed(2);
    if (posCheckoutModal) posCheckoutModal.show();
  };

  window.submitPOSOrder = function () {
    const btn = document.getElementById("btnPOSPlaceOrder");
    const method = document.getElementById("posPaymentMethod").value;

    const payload = {
      payment_method: method,
      items: posCart.map((c) => ({
        item_id: c.id,
        quantity: c.qty,
        remarks: c.remarks,
      })),
    };

    btn.disabled = true;
    btn.innerHTML = `<i class="fas fa-spinner fa-spin me-2"></i> Processing...`;

    fetch("../api/checkout_handler.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(payload),
    })
      .then((res) => res.json())
      .then((data) => {
        btn.disabled = false;
        btn.innerHTML = `<i class="fas fa-check me-2"></i> Paid & Submit to Kitchen`;

        if (data.status === "success") {
          if (posCheckoutModal) posCheckoutModal.hide();
          if (walkInPOSModal) walkInPOSModal.hide();
          posCart = [];
          updatePOSCartUI();
          loadDashboard();
          alert(`Order #${data.order_id} placed and sent to kitchen!`);
        } else {
          alert("Error: " + (data.message || "Failed to submit order."));
        }
      })
      .catch((err) => {
        btn.disabled = false;
        btn.innerHTML = `<i class="fas fa-check me-2"></i> Paid & Submit to Kitchen`;
        alert("Network error. Could not place walk-in order.");
      });
  };

  function openCompletedOrdersModal() {
    loadCompletedOrders();
    if (completedModal) completedModal.show();
  }

  function loadCompletedOrders() {
    if (els.completedOrderTable) {
      els.completedOrderTable.innerHTML = '<tr><td colspan="5" class="text-center py-4"><i class="fas fa-spinner fa-spin me-2"></i>Loading completed orders...</td></tr>';
    }

    fetch("getorder.php?type=completed")
      .then((res) => res.json())
      .then((data) => {
        if (data.success || data.status === 'success') {
          completedOrdersCache = data.orders || data.data || [];
          renderCompletedTable(completedOrdersCache);
        }
      })
      .catch((err) => {
        if (els.completedOrderTable) {
          els.completedOrderTable.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-danger">Could not load completed orders.</td></tr>';
        }
      });
  }

  function renderCompletedTable(orders) {
    if (!orders || !orders.length) {
      els.completedOrderTable.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-muted">No completed orders today yet.</td></tr>';
      return;
    }

    els.completedOrderTable.innerHTML = orders.map(order => `
      <tr data-order-id="${order.order_id}" class="clickable-row" style="cursor: pointer;">
        <td><strong>#${order.order_id}</strong></td>
        <td class="op-time">${formatTime(order.order_time)}</td>
        <td><span class="badge-payment paid">${order.payment_status} (${order.payment_method})</span></td>
        <td class="op-total">RM ${Number(order.total_amount).toFixed(2)}</td>
        <td style="text-align: right;">
          <button type="button" class="btn btn-sm btn-outline-primary fw-bold me-1" data-action="view-completed-receipt" data-order-id="${order.order_id}">
            <i class="fas fa-receipt me-1"></i> Receipt
          </button>
          <button type="button" class="btn btn-sm btn-outline-danger fw-bold" data-action="delete-completed" data-order-id="${order.order_id}">
            <i class="fas fa-trash-can"></i>
          </button>
        </td>
      </tr>`).join("");
  }

  function formatTime(dateString) {
    const d = new Date(dateString);
    if (isNaN(d)) return dateString;
    return d.toLocaleTimeString([], { hour: "2-digit", minute: "2-digit" });
  }

  function openOrderModal(orderId, isCompleted = false) {
    const cache = isCompleted ? completedOrdersCache : ordersCache;
    let order = cache.find((o) => String(o.order_id) === String(orderId)) ||
                ordersCache.find((o) => String(o.order_id) === String(orderId)) ||
                completedOrdersCache.find((o) => String(o.order_id) === String(orderId));
    if (!order) return;

    document.getElementById("modalOrderTitle").textContent = `Order #${order.order_id} Details`;
    document.getElementById("modalKitchenStatus").textContent = order.kitchen_status;
    document.getElementById("modalPaymentStatus").textContent = `${order.payment_status} (${order.payment_method})`;
    document.getElementById("modalTotalAmount").textContent = Number(order.total_amount).toFixed(2);
    document.getElementById("modalOrderId").value = order.order_id;

    const itemList = document.getElementById("modalItemList");
    const items = order.items || [];
    itemList.innerHTML = items.length
      ? items.map((item) => `
        <li class="list-group-item d-flex justify-content-between align-items-start py-2">
          <div>
            <strong class="d-block">${item.qty}x ${escapeHtml(item.name)}</strong>
            ${item.remarks ? `<small class="text-danger italic">Note: ${escapeHtml(item.remarks)}</small>` : ''}
          </div>
          <span class="fw-bold">RM ${Number(item.subtotal).toFixed(2)}</span>
        </li>`).join("")
      : '<li class="list-group-item text-muted">No item details available.</li>';

    const footer = document.getElementById("modalFooterActions");
    if (order.payment_status === "Paid") {
      footer.innerHTML = `
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-op-primary" onclick="window.print()">
          <i class="fas fa-print me-1"></i> Print Receipt
        </button>`;
    } else {
      footer.innerHTML = `
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-success fw-bold" id="modalPayBtn">
          <i class="fas fa-check me-1"></i> Approve Payment & Send to Kitchen
        </button>`;
      document.getElementById("modalPayBtn").addEventListener("click", function() {
        approveAndPay(order.order_id, this);
      });
    }

    if (orderModal) orderModal.show();
  }

  function approveAndPay(orderId, btnElement) {
    if (btnElement) {
      btnElement.disabled = true;
      btnElement.innerHTML = `<i class="fas fa-spinner fa-spin"></i> Processing...`;
    }

    const order = ordersCache.find((o) => String(o.order_id) === String(orderId));
    const payMethod = order ? order.payment_method : "Cash";

    fetch("process_payment.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ order_id: orderId, payment_method: payMethod }),
    })
      .then((res) => res.json())
      .then((data) => {
        if (data.success || data.status === 'success') {
          if (orderModal) orderModal.hide();
          loadDashboard();
        } else {
          throw new Error(data.message || "Approval failed");
        }
      })
      .catch((err) => {
        alert("Could not process approval: " + err.message);
        loadDashboard();
      });
  }

  function cancelOrder(orderId, btnElement) {
    if (!confirm("Are you sure you want to cancel and reject this order?")) return;

    if (btnElement) {
      btnElement.disabled = true;
      btnElement.innerHTML = `<i class="fas fa-spinner fa-spin"></i>`;
    }

    fetch("update_order_status.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ order_id: orderId, kitchen_status: "cancelled" }),
    })
      .then((res) => res.json())
      .then((data) => {
        if (data.success || data.status === 'success') {
          loadDashboard();
        } else {
          throw new Error(data.message || "Cancel failed");
        }
      })
      .catch((err) => {
        alert("Could not cancel order: " + err.message);
        loadDashboard();
      });
  }

  function deleteCompletedOrder(orderId, btnElement) {
    if (!confirm(`Are you sure you want to clear completed Order #${orderId}?`)) return;

    if (btnElement) {
      btnElement.disabled = true;
      btnElement.innerHTML = `<i class="fas fa-spinner fa-spin"></i>`;
    }

    fetch("update_order_status.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ order_id: orderId, kitchen_status: "cancelled" }),
    })
      .then((res) => res.json())
      .then((data) => {
        if (data.success || data.status === 'success') {
          loadCompletedOrders();
          loadDashboard();
        } else {
          throw new Error(data.message || "Delete failed");
        }
      })
      .catch((err) => {
        alert("Could not delete order: " + err.message);
        loadCompletedOrders();
      });
  }

  function clearAllCompletedOrders() {
    if (!confirm("Are you sure you want to clear ALL completed orders for today?")) return;

    if (els.btnClearAllCompleted) {
      els.btnClearAllCompleted.disabled = true;
      els.btnClearAllCompleted.innerHTML = `<i class="fas fa-spinner fa-spin me-1"></i> Clearing...`;
    }

    fetch("update_order_status.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ action: "clear_all_completed" }),
    })
      .then((res) => res.json())
      .then((data) => {
        if (els.btnClearAllCompleted) {
          els.btnClearAllCompleted.disabled = false;
          els.btnClearAllCompleted.innerHTML = `<i class="fas fa-trash-can me-1"></i> Clear All Completed`;
        }
        if (data.success || data.status === 'success') {
          loadCompletedOrders();
          loadDashboard();
        } else {
          throw new Error(data.message || "Clear all failed");
        }
      })
      .catch((err) => {
        if (els.btnClearAllCompleted) {
          els.btnClearAllCompleted.disabled = false;
          els.btnClearAllCompleted.innerHTML = `<i class="fas fa-trash-can me-1"></i> Clear All Completed`;
        }
        alert("Could not clear completed orders: " + err.message);
      });
  }

  function escapeHtml(str) {
    const div = document.createElement("div");
    div.textContent = str;
    return div.innerHTML;
  }
})();
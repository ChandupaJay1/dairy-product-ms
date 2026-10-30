<!-- ========================================================
     MVC VIEW PARTIAL: MODALS COMPONENT
     ======================================================== -->

<!-- MODAL 1: Driver SOS Breakdown Emergency Modal -->
<div class="modal-overlay" id="sosModal">
    <div class="modal-content-box border-pulse-red">
        <div class="modal-header">
            <h3 class="text-danger" data-i18n="sos_modal_title">🚨 Trigger Emergency Vehicle SOS</h3>
            <button class="modal-close-btn" onclick="DriverView.closeSosModal()">&times;</button>
        </div>
        <div class="modal-body">
            <p class="text-sm text-muted mb-3" data-i18n="sos_warning">
                Broadcasting SOS alerts the Front Office dispatch team in Polonnaruwa immediately and initiates the stock rescue protocol.
            </p>

            <div class="form-group">
                <label data-i18n="sos_reason_lbl">Breakdown Reason:</label>
                <select id="sosReasonSelect" class="form-control" onchange="DriverView.onSosReasonChange(this.value)">
                    <option value="Refrigeration Compressor Fault - Chiller Temp Rising">Refrigeration Compressor Fault (Chiller Temp Rising)</option>
                    <option value="Engine Breakdown / Alternator Belt Snapped">Engine Breakdown / Alternator Belt Snapped</option>
                    <option value="Axle Damage / Severe Tyre Blowout">Axle Damage / Severe Tyre Blowout</option>
                    <option value="Vehicle Stuck in Flood / Rural Mud Track">Vehicle Stuck in Flood / Rural Mud Track</option>
                    <option value="Transit Road Accident">Transit Road Accident</option>
                    <option value="OTHER" data-i18n="sos_reason_other">⚠️ Other Reason (Specify below)...</option>
                </select>
            </div>

            <div class="form-group" id="sosOtherReasonContainer" style="display:none; margin-top: -0.25rem;">
                <label for="sosOtherReasonInput" data-i18n="sos_other_label" style="color: var(--accent-amber, #f59e0b); font-weight: 700; font-size: 0.85rem;">
                    ✏️ Specify Breakdown Reason:
                </label>
                <textarea id="sosOtherReasonInput" class="form-control" rows="2" 
                    placeholder="Describe the breakdown issue in detail (e.g. Battery dead, gear box issue, fuel leak)..." 
                    data-i18n-placeholder="sos_other_prompt"
                    style="font-size: 0.88rem; border-color: rgba(245, 158, 11, 0.4);"></textarea>
            </div>

            <div class="form-group">
                <label data-i18n="sos_landmark_lbl">Current Landmark / Location:</label>
                <input type="text" id="sosLandmarkInput" class="form-control" value="Near Hingurakgoda Track - Sector B (Km 14)">
            </div>

            <div class="form-group">
                <label data-i18n="sos_temp_lbl">Current Chiller Temperature (°C):</label>
                <input type="number" id="sosTempInput" class="form-control" value="11.8" step="0.1">
            </div>

            <div class="card p-3 my-3" style="background: var(--color-sos-soft); border: 1px solid rgba(244, 63, 94, 0.3); border-radius: var(--radius-md);">
                <div class="text-xs" style="color: var(--text-muted);" data-i18n="sos_risk_cups_lbl">Remaining Cups on Truck at Risk:</div>
                <div class="text-xl font-bold" style="color: var(--color-sos); font-family: monospace;" id="sosRemainingStockDisplay">1,320</div>
            </div>

            <button class="btn btn-sos btn-block font-bold mt-3" onclick="DriverView.submitSosBreakdown()" data-i18n="dispatch_sos_btn">
                🚨 Broadcast SOS Breakdown Alert Now
            </button>
        </div>
    </div>
</div>

<!-- MODAL 2: Admin Dispatch Rescue Vehicle Modal -->
<div class="modal-overlay" id="dispatchRescueModal">
    <div class="modal-content-box">
        <div class="modal-header">
            <h3 data-i18n="dispatch_rescue_title">🚐 Dispatch Rescue Vehicle</h3>
            <button class="modal-close-btn" onclick="AdminDashboard.closeDispatchRescueModal()">&times;</button>
        </div>
        <div class="modal-body">
            <p class="text-sm text-muted mb-3" data-i18n="dispatch_rescue_sub">
                Assign a standby cold-van and driver from the Polonnaruwa fleet to rescue the stranded yogurt inventory.
            </p>
            <div class="form-group">
                <label data-i18n="select_rescue_vehicle">Select Rescue Vehicle:</label>
                <select id="rescueVehicleSelect" class="form-control"></select>
            </div>
            <div class="form-group">
                <label data-i18n="select_rescue_driver">Select Rescue Driver:</label>
                <select id="rescueDriverSelect" class="form-control"></select>
            </div>
            <div class="form-group">
                <label data-i18n="rescue_notes_lbl">Dispatch Notes / Instructions:</label>
                <input type="text" id="rescueNotesInput" class="form-control" value="Proceed with high urgency via Hingurakgoda bypass.">
            </div>
            <button class="btn btn-success btn-block mt-3 font-bold" onclick="AdminDashboard.submitDispatchRescue()" data-i18n="btn_submit_dispatch_rescue">
                ⚡ Dispatch Rescue Team Immediately
            </button>
        </div>
    </div>
</div>

<!-- MODAL 3: Stock Transfer Handshake Modal (Field Count Verification) -->
<div class="modal-overlay" id="transferHandshakeModal">
    <div class="modal-content-box">
        <div class="modal-header">
            <h3 data-i18n="handshake_modal_title">🤝 Field Stock Rescue Handshake</h3>
            <button class="modal-close-btn" onclick="App.closeTransferHandshakeModal()">&times;</button>
        </div>
        <div class="modal-body">
            <div id="handshakeContextCard" class="card card-subtle p-2 mb-3" style="font-size:0.85rem; display:none;">
                <div class="flex-between">
                    <span>SOS Ticket: <strong id="handshakeTicketDisplay" class="text-danger">--</strong></span>
                    <span>Remaining at Risk: <strong id="handshakeRiskCupsDisplay" class="text-warning">--</strong> cups</span>
                </div>
            </div>
            <p class="text-sm text-muted mb-3" data-i18n="handshake_modal_sub">
                Log the verified physical count between the stranded driver and the rescue driver before transferring inventory.
            </p>
            <div class="form-group">
                <label data-i18n="verified_good_cups">Verified Good Cups (Usable & Rescued):</label>
                <input type="number" id="transferTotalCups" class="form-control" value="1300" min="0">
            </div>
            <div class="form-group">
                <label data-i18n="spoiled_cups_lbl">Spoiled / Heat-Damaged Cups:</label>
                <input type="number" id="transferSpoiledCups" class="form-control" value="0" min="0">
            </div>
            <div class="form-group">
                <label data-i18n="handshake_notes_lbl">Dual-Driver Sign-off Notes:</label>
                <input type="text" id="transferHandshakeNotes" class="form-control" value="Physical count verified jointly by Driver & Rescue Driver.">
            </div>
            <button class="btn btn-success btn-block mt-3 font-bold" onclick="App.submitTransferHandshake()" data-i18n="btn_confirm_handshake">
                ✅ Confirm Handshake & Transfer Manifest Stock
            </button>
        </div>
    </div>
</div>

<!-- MODAL 4: Credit Authorization Modal (Canvas Signature & OTP) -->
<div class="modal-overlay" id="creditAuthModal">
    <div class="modal-content-box credit-modal-box">
        <div class="modal-header">
            <div style="display:flex; align-items:center; gap:8px;">
                <span style="font-size:1.2rem;">📝</span>
                <div>
                    <h3 data-i18n="credit_modal_title" style="margin:0; font-size:1.05rem;">Credit Sale Legal Binding</h3>
                    <p style="margin:0; font-size:0.72rem; color:var(--text-muted);">Verified Shopkeeper Authorization</p>
                </div>
            </div>
            <button class="modal-close-btn" onclick="SalesRepWizard.closeCreditAuthModal()">&times;</button>
        </div>
        <div class="modal-body">
            <!-- Shop & Amount Summary Banner -->
            <div class="credit-summary-card">
                <div class="credit-summary-shop">
                    <span class="text-xs text-muted" data-i18n="shop_lbl">Customer / Shop:</span>
                    <strong id="creditShopNameDisplay" class="credit-shop-name">--</strong>
                </div>
                <div class="credit-summary-amount">
                    <span class="text-xs text-muted" data-i18n="amount_lbl">Invoice Total:</span>
                    <strong class="credit-amount-val" id="creditAmountDisplay">Rs. 0.00</strong>
                </div>
            </div>

            <!-- Segmented Auth Method Tabs (Signature vs SMS OTP) -->
            <div class="auth-segmented-tabs">
                <button type="button" class="auth-tab-btn active" id="tabBtnSignature" onclick="SalesRepWizard.switchAuthMethod('signature')">
                    <span data-i18n="tab_signature">✍️ Digital Signature</span>
                </button>
                <button type="button" class="auth-tab-btn" id="tabBtnOtp" onclick="SalesRepWizard.switchAuthMethod('otp')">
                    <span data-i18n="tab_otp">📱 SMS OTP Code</span>
                </button>
            </div>

            <!-- Panel 1: Digital Signature Pad -->
            <div id="authPanelSignature" class="auth-panel-content">
                <div class="signature-pad-wrapper">
                    <!-- Pad Top Controls -->
                    <div class="signature-pad-header">
                        <span class="sig-header-tag">
                            <span>✍️</span> Touch / Stylus Sign
                        </span>
                        <button type="button" class="btn-clear-signature" onclick="SalesRepWizard.clearSignature()" title="Clear signature pad" data-i18n="clear_sig_btn">
                            <span>🧹</span> Clear Pad
                        </button>
                    </div>

                    <!-- Canvas -->
                    <canvas id="signatureCanvas" class="signature-canvas-element"></canvas>

                    <!-- Legal Baseline -->
                    <div class="signature-baseline-guide">
                        <span class="sig-x-mark">✕</span>
                        <div class="sig-dotted-line"></div>
                    </div>

                    <!-- Watermark -->
                    <div class="signature-watermark" data-i18n="credit_sign_here">
                        Sign on the line above &bull; Finger or Stylus
                    </div>
                </div>
            </div>

            <!-- Panel 2: SMS OTP Verification -->
            <div id="authPanelOtp" class="auth-panel-content" style="display:none;">
                <div class="otp-card-wrapper">
                    <div class="otp-phone-row">
                        <div class="otp-phone-label">
                            <span class="text-xs text-muted" data-i18n="phone_lbl">Registered Mobile:</span>
                            <span class="otp-phone-badge" id="creditPhoneDisplay">--</span>
                        </div>
                        <button type="button" class="btn btn-xs btn-brand font-bold" onclick="SalesRepWizard.generateCreditOtp()" id="btnGenerateOtp" data-i18n="send_otp_btn">
                            📲 Send SMS OTP
                        </button>
                    </div>
                    
                    <div style="margin-top:0.85rem; text-align:center;">
                        <label for="otpInput" class="text-xs text-muted" style="display:block; margin-bottom:6px;" data-i18n="otp_prompt">
                            Enter 6-digit OTP received on shop owner's phone:
                        </label>
                        <input type="text" id="otpInput" maxlength="6" class="form-control otp-large-input" placeholder="••••••" autocomplete="one-time-code">
                        <div class="text-xs text-warning mt-1" id="otpTimerDisplay" style="min-height:18px;"></div>
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <button class="btn btn-brand btn-block font-bold credit-submit-btn" onclick="SalesRepWizard.submitCreditOrder()" data-i18n="confirm_credit_btn">
                <span>🔒</span> Authorize & Issue Credit Order
            </button>
        </div>
    </div>
</div>

<!-- MODAL 5: Printable Digital Invoice & Tamper-Proof Receipt Modal -->
<div class="modal-overlay" id="receiptModal">
    <div class="modal-content-box" style="max-width: 460px;">
        <div class="modal-header">
            <h3 data-i18n="receipt_title">🧾 Transaction Invoice Receipt</h3>
            <button class="modal-close-btn" onclick="SalesRepWizard.closeReceiptModal()">&times;</button>
        </div>
        <div class="modal-body">
            <div class="receipt-paper" id="printableReceipt">
                <div class="receipt-header-center">
                    <h3 style="margin: 0; font-size: 1.1rem;">POLONNARUWA YOGURT CO.</h3>
                    <p style="font-size: 0.72rem; margin: 2px 0;">Fresh Dairy Distribution &bull; Polonnaruwa Sector</p>
                    <p style="font-size: 0.72rem; margin: 0;">Tel: 027-2224501 / 077-1234567</p>
                </div>

                <div style="font-size: 0.78rem; display: flex; justify-content: space-between; margin-bottom: 6px;">
                    <span><span data-i18n="invoice_no">Invoice:</span> <strong id="receiptInvoiceNo">--</strong></span>
                    <span id="receiptDate">--</span>
                </div>

                <div style="font-size: 0.78rem; border-bottom: 1px dotted #CBD5E1; padding-bottom: 6px; margin-bottom: 8px;">
                    <div><span data-i18n="customer_shop">Shop:</span> <strong id="receiptShopName">--</strong></div>
                    <div style="font-size: 0.7rem; color: #64748B;" id="receiptShopAddr">--</div>
                    <div class="mt-1">
                        Payment: <span class="badge" id="receiptPaymentType">CASH</span>
                    </div>
                </div>

                <table class="receipt-items-table">
                    <thead>
                        <tr style="border-bottom: 1px solid #0F172A;">
                            <th>Item</th>
                            <th class="text-center">Qty</th>
                            <th class="text-right">Price</th>
                            <th class="text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody id="receiptItemsTbody"></tbody>
                </table>

                <div style="border-top: 2px dashed #CBD5E1; padding-top: 8px; margin-top: 10px; display: flex; justify-content: space-between; font-size: 1.05rem; font-weight: bold;">
                    <span>GRAND TOTAL:</span>
                    <span id="receiptTotal">Rs. 0.00</span>
                </div>

                <div id="receiptSignatureDisplay" class="mt-2 text-center"></div>

                <div class="receipt-seal-box">
                    <div style="font-weight: bold; font-size: 0.65rem; color: #475569;" data-i18n="tamper_seal_lbl">
                        🔒 Verification Code:
                    </div>
                    <div class="font-mono text-xs" id="receiptTamperSeal" style="color: #0F172A;">--</div>
                    <div style="font-size: 0.62rem; color: #16A34A; margin-top: 3px;" data-i18n="verified_sig_lbl">
                        ✓ Digitally Verified Invoice
                    </div>
                </div>
            </div>

            <div class="mt-3">
                <button type="button" class="btn btn-whatsapp btn-block font-bold mb-2" onclick="SalesRepWizard.shareReceiptViaWhatsApp()" style="background:#25D366; color:white; font-size:1.05rem; padding:12px; border-radius:8px; display:flex; align-items:center; justify-content:center; gap:8px; border:none; cursor:pointer;" data-i18n="btn_share_whatsapp">
                    <span style="font-size:1.25rem;">📲</span> WhatsApp මගින් බිල යවන්න (Share via WhatsApp)
                </button>
            </div>

            <div class="flex-between gap-2">
                <button class="btn btn-secondary btn-block font-bold" onclick="window.print()" data-i18n="print_receipt_btn">
                    🖨️ Print Receipt
                </button>
                <button class="btn btn-primary btn-block font-bold" onclick="SalesRepWizard.closeReceiptModal()" data-i18n="close_btn">
                    Done
                </button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL 6: Daily Fleet Dynamic Rotation Modal -->
<div class="modal-overlay" id="rotationModal">
    <div class="modal-content-box">
        <div class="modal-header">
            <h3 data-i18n="rotate_fleet_modal_title">🔄 Dynamic Fleet & Driver Rotation</h3>
            <button class="modal-close-btn" onclick="AdminDashboard.closeRotationModal()">&times;</button>
        </div>
        <div class="modal-body">
            <p class="text-sm text-muted mb-3" data-i18n="rotate_fleet_desc">
                Rotate drivers and Polonnaruwa routes while inventory follows assigned vehicle manifests.
            </p>
            <div class="form-group">
                <label data-i18n="label_select_vehicle">Select Vehicle:</label>
                <select id="rotateVehicleSelect" class="form-control" onchange="AdminDashboard.onRotateVehicleChange(this.value)"></select>
            </div>
            <div class="form-group">
                <label data-i18n="label_assign_driver">Assign Driver:</label>
                <select id="rotateDriverSelect" class="form-control"></select>
            </div>
            <div class="form-group">
                <label data-i18n="label_assign_route">Assign Route:</label>
                <select id="rotateRouteSelect" class="form-control"></select>
            </div>
            <button class="btn btn-primary btn-block mt-3 font-bold" onclick="AdminDashboard.submitRotation()" data-i18n="btn_save_rotation">
                Save Daily Rotation Assignment
            </button>
        </div>
    </div>
</div>

<!-- MODAL 7: Shop Outstanding Payment Modal -->
<div class="modal-overlay" id="shopPaymentModal">
    <div class="modal-content-box">
        <div class="modal-header">
            <h3 data-i18n="shop_payment_modal_title">💵 Receive Shop Credit Payment</h3>
            <button class="modal-close-btn" onclick="AdminDashboard.closePaymentModal()">&times;</button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label data-i18n="shop_lbl">Shop:</label>
                <div class="font-bold text-main" id="paymentShopName">--</div>
            </div>
            <div class="form-group">
                <label data-i18n="current_balance_lbl">Current Outstanding Balance:</label>
                <div class="text-danger font-bold" id="paymentCurrentBalance">Rs. 0.00</div>
            </div>
            <div class="form-group">
                <label data-i18n="payment_amount_lbl">Payment Amount Received (Rs.):</label>
                <input type="number" id="paymentAmountInput" class="form-control" step="100">
            </div>
            <button class="btn btn-success btn-block mt-3 font-bold" onclick="AdminDashboard.submitShopPayment()" data-i18n="btn_record_payment">
                Record Payment & Restore Credit Limit
            </button>
        </div>
    </div>
</div>

<!-- MODAL 8: New Morning Load Dispatch Modal -->
<div class="modal-overlay" id="newDispatchModal">
    <div class="modal-content-box">
        <div class="modal-header">
            <h3 data-i18n="new_dispatch_modal_title">🥛 Create Morning Load Dispatch</h3>
            <button class="modal-close-btn" onclick="AdminDashboard.closeNewDispatchModal()">&times;</button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label data-i18n="delivery_vehicle_lbl">Delivery Vehicle:</label>
                <select id="dispVehicleSelect" class="form-control" onchange="AdminDashboard.onDispatchVehicleChange(this.value)"></select>
            </div>
            <div class="form-group">
                <label data-i18n="assigned_driver_lbl">Assigned Driver:</label>
                <select id="dispDriverSelect" class="form-control"></select>
            </div>
            <div class="form-group">
                <label data-i18n="assigned_sales_rep_lbl">Assigned Sales Representative:</label>
                <select id="dispSalesRepSelect" class="form-control"></select>
            </div>
            <div class="form-group">
                <label data-i18n="route_sector_lbl">Route Sector:</label>
                <select id="dispRouteSelect" class="form-control"></select>
            </div>

            <!-- Real-time Capacity Gauge -->
            <div id="dispCapacitySummary" class="card card-subtle p-2 mb-2 text-xs" style="background:var(--bg-surface-elevated); border:1px solid var(--border-subtle); border-radius:8px;">
                <div class="flex-between">
                    <span>Total Load: <strong id="dispTotalCupsDisplay" class="font-mono text-cyan" style="font-size:0.95rem;">1,500</strong> cups</span>
                    <span>Vehicle Capacity: <strong id="dispVehicleCapacityDisplay" class="font-mono">2,500</strong> cups</span>
                </div>
                <div class="progress-bar-track mt-1" style="height:6px; background:rgba(255,255,255,0.08); border-radius:4px; overflow:hidden;">
                    <div id="dispCapacityProgressBar" class="progress-bar-fill" style="width:60%; height:100%; background:var(--color-primary, #06b6d4); transition:width 0.2s;"></div>
                </div>
                <div id="dispCapacityWarning" class="text-danger font-bold mt-1" style="display:none;">⚠️ Warning: Total load exceeds vehicle capacity!</div>
            </div>

            <div class="card card-subtle p-3 my-2">
                <div class="font-bold text-xs text-muted mb-2" data-i18n="flavor_load_quantities">FLAVOR LOAD QUANTITIES (CUPS):</div>
                <div class="grid grid-2 gap-2" style="display:grid; grid-template-columns: 1fr 1fr; gap:8px;">
                    <div>
                        <label class="text-xs">Vanilla (80g):</label>
                        <input type="number" id="dispVanilla" class="form-control" value="600" min="0" oninput="AdminDashboard.updateDispatchLoadTotal()">
                    </div>
                    <div>
                        <label class="text-xs">Strawberry (80g):</label>
                        <input type="number" id="dispStrawberry" class="form-control" value="350" min="0" oninput="AdminDashboard.updateDispatchLoadTotal()">
                    </div>
                    <div>
                        <label class="text-xs">Chocolate (80g):</label>
                        <input type="number" id="dispChocolate" class="form-control" value="250" min="0" oninput="AdminDashboard.updateDispatchLoadTotal()">
                    </div>
                    <div>
                        <label class="text-xs">Mango (80g):</label>
                        <input type="number" id="dispMango" class="form-control" value="200" min="0" oninput="AdminDashboard.updateDispatchLoadTotal()">
                    </div>
                    <div style="grid-column: span 2;">
                        <label class="text-xs">Curd & Treacle Pot (180g):</label>
                        <input type="number" id="dispTreacle" class="form-control" value="100" min="0" oninput="AdminDashboard.updateDispatchLoadTotal()">
                    </div>
                </div>
            </div>

            <button class="btn btn-primary btn-block mt-3 font-bold" onclick="AdminDashboard.submitNewDispatch()" data-i18n="btn_confirm_morning_dispatch">
                Confirm Morning Dispatch Load
            </button>
        </div>
    </div>
</div>

<!-- MODAL 8B: Manifest Details & Breakdown Modal -->
<div class="modal-overlay" id="manifestDetailsModal">
    <div class="modal-content-box" style="max-width: 600px;">
        <div class="modal-header">
            <h3 data-i18n="manifest_modal_title">📋 Manifest Details & Inventory Breakdown</h3>
            <button class="modal-close-btn" onclick="AdminDashboard.closeManifestDetailsModal()">&times;</button>
        </div>
        <div class="modal-body" id="manifestDetailsModalBody">
            <!-- Dynamically populated by AdminDashboard.openManifestDetailsModal -->
        </div>
    </div>
</div>

<!-- MODAL 8C: Edit Shop Credit Tier & Limit Modal -->
<div class="modal-overlay" id="editShopCreditModal">
    <div class="modal-content-box" style="max-width: 440px;">
        <div class="modal-header">
            <h3 data-i18n="edit_credit_modal_title">⚙️ Adjust Shop Credit Tier & Limit</h3>
            <button class="modal-close-btn" onclick="AdminDashboard.closeEditShopCreditModal()">&times;</button>
        </div>
        <div class="modal-body">
            <div class="form-group mb-2">
                <label data-i18n="th_shop_name">Shop Name:</label>
                <div class="font-bold text-main" id="editShopCreditName" style="font-size:1.05rem;">--</div>
                <div class="text-xs text-muted" id="editShopCreditPhone">--</div>
            </div>
            <div class="form-group mb-2">
                <label data-i18n="current_balance_lbl">Current Outstanding Balance:</label>
                <div class="font-bold text-danger" id="editShopCreditBalance" style="font-size:1rem;">Rs. 0.00</div>
            </div>
            <div class="form-group mb-2">
                <label data-i18n="th_tier">Credit Risk Tier:</label>
                <select id="editShopCreditTier" class="form-control">
                    <option value="Tier A">Tier A (Elite / 30 Days Credit / High Limit)</option>
                    <option value="Tier B">Tier B (Standard / 14 Days Credit)</option>
                    <option value="Tier C">Tier C (Restricted / 7 Days Credit)</option>
                    <option value="Cash Only">Cash Only (No Credit Allowed)</option>
                </select>
            </div>
            <div class="form-group mb-3">
                <label data-i18n="th_limit">Credit Limit (Rs.):</label>
                <input type="number" id="editShopCreditLimit" class="form-control font-mono font-bold" min="0" step="1000">
            </div>
            <button class="btn btn-primary btn-block font-bold" onclick="AdminDashboard.submitEditShopCredit()" data-i18n="btn_save_credit_tier">
                💾 Save Credit Terms
            </button>
        </div>
    </div>
</div>

<!-- MODAL 9: Mobile App Installation Guide -->
<div class="modal-overlay" id="pwaInstallModal">
    <div class="modal-content-box" style="max-width: 480px;">
        <div class="modal-header">
            <h3 data-i18n="pwa_modal_title">📲 Mobile App Installation Guide</h3>
            <button class="modal-close-btn" onclick="document.getElementById('pwaInstallModal').style.display='none'">&times;</button>
        </div>
        <div class="modal-body">
            <div class="text-center mb-3">
                <img src="/assets/logo.jpg" alt="Polonnaruwa Yogurt Logo" style="width: 58px; height: 58px; border-radius: 12px; border: 2px solid var(--cyan-accent); margin-bottom: 6px; object-fit: cover;">
                <h4 style="margin: 0; font-size: 1.05rem;">Polonnaruwa Yogurt Distribution</h4>
                <p class="text-xs text-muted mt-1" data-i18n="pwa_modal_sub">Play Store / App Store අවශ්‍ය නැත &bull; Standalone Offline App</p>
            </div>

            <div class="card card-subtle p-3 mb-2">
                <h5 class="text-xs font-bold text-info mb-1">🤖 ANDROID (GOOGLE CHROME):</h5>
                <ol class="text-xs text-sub" style="padding-left: 18px; line-height: 1.7; margin: 0;">
                    <li>Chrome හි ඉහළ දකුණු කෙළවරේ ඇති තිත් තුන (<strong>⋮ Menu</strong>) ඔබන්න.</li>
                    <li>මෙනුවෙන් <strong>"Install app"</strong> හෝ <strong>"Add to Home screen"</strong> තෝරන්න.</li>
                    <li><strong>"Install"</strong> හෝ <strong>"Add"</strong> ඔබන්න. එවිට ඔබගේ Phone එකේ සාමාන්‍ය ඇප් එකක් ලෙස Install වේ.</li>
                </ol>
            </div>

            <div class="card card-subtle p-3 mb-2">
                <h5 class="text-xs font-bold text-success mb-1">🍎 iPHONE / iPAD (APPLE SAFARI):</h5>
                <ol class="text-xs text-sub" style="padding-left: 18px; line-height: 1.7; margin: 0;">
                    <li>Safari බ්‍රවුසරයේ පහළ ඇති <strong>Share</strong> ලකුණ (<strong>⎋ කොටුවෙන් උඩට ඊතලය</strong>) ඔබන්න.</li>
                    <li>පහළට Scroll කර <strong>"Add to Home Screen ➕"</strong> තෝරන්න.</li>
                    <li>ඉහළ දකුණේ <strong>"Add"</strong> ඔබන්න. එවිට Home Screen එකට App එකක් ලෙස එක් වේ.</li>
                </ol>
            </div>

            <div class="card card-subtle p-3 mb-3">
                <h5 class="text-xs font-bold text-warning mb-1">💻 PC / LAPTOP (CHROME / EDGE):</h5>
                <p class="text-xs text-sub" style="line-height: 1.6; margin: 0;">
                    URL Address Bar එකේ දකුණු කෙළවරේ ඇති <strong>Install App ⊕</strong> අයිකනය ක්ලික් කර Install කර ගන්න.
                </p>
            </div>

            <div id="pwaPromptDirectBtnBox" style="display:none;" class="mb-2">
                <button class="btn btn-primary btn-block font-bold" onclick="App.triggerDirectInstall()">
                    ⚡ One-Tap Native Install Prompt
                </button>
            </div>

            <button class="btn btn-secondary btn-block font-bold" onclick="document.getElementById('pwaInstallModal').style.display='none'" data-i18n="btn_close">
                හරි, තේරුණා (Close)
            </button>
        </div>
    </div>
</div>

<!-- MODAL 9: Log Stock Return & Expiry Adjustment -->
<div class="modal-overlay" id="stockReturnModal">
    <div class="modal-content-box" style="max-width: 520px;">
        <div class="modal-header">
            <h3 data-i18n="modal_return_title">🥛 නරක් වූ / කල්ඉකුත් වූ යෝගට් බාරගැනීම (Stock Return)</h3>
            <button class="modal-close-btn" onclick="document.getElementById('stockReturnModal').style.display='none'">&times;</button>
        </div>
        <div class="modal-body">
            <p class="text-sm text-muted mb-3" data-i18n="modal_return_sub">
                කඩයෙන් ආපසු ලබාගත් නරක් වූ හෝ කල්ඉකුත් වූ යෝගට් සටහන් කරන්න. (ස්වයංක්‍රීයව කඩයේ ණයෙන් අඩු වේ).
            </p>

            <div class="form-group">
                <label class="font-bold" data-i18n="modal_select_shop">1. කඩය තෝරන්න (Select Shop):</label>
                <select id="returnShopSelect" class="form-control font-bold" style="font-size:0.95rem;"></select>
            </div>

            <div class="grid grid-2 gap-2" style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
                <div class="form-group">
                    <label class="font-bold" data-i18n="modal_select_flavor">2. යෝගට් වර්ගය (Flavor):</label>
                    <select id="returnFlavorSelect" class="form-control" onchange="SalesRepWizard.onReturnFlavorChange()">
                        <option value="Fresh Vanilla Yogurt (80g)" data-price="80.00">🍦 Vanilla - රු. 80</option>
                        <option value="Sweet Strawberry Yogurt (80g)" data-price="90.00">🍓 Strawberry - රු. 90</option>
                        <option value="Creamy Chocolate Yogurt (80g)" data-price="95.00">🍫 Chocolate - රු. 95</option>
                        <option value="Tropical Mango Yogurt (80g)" data-price="95.00">🥭 Mango - රු. 95</option>
                        <option value="Traditional Curd & Kithul Treacle Pot (180g)" data-price="160.00">🏺 Curd Pot - රු. 160</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="font-bold" data-i18n="return_batch_lbl">3. බැච් අංකය (Batch No):</label>
                    <input type="text" id="returnBatchInput" class="form-control" value="LOT-20261014-A" placeholder="උදා: LOT-20261014">
                </div>

                <div class="form-group">
                    <label class="font-bold" data-i18n="modal_select_reason">4. හේතුව (Reason):</label>
                    <select id="returnReasonSelect" class="form-control">
                        <option value="EXPIRED">කල් ඉකුත් වූ (Expired)</option>
                        <option value="LEAK_DAMAGED">කාන්දු වූ / පියන කැඩුනු (Leaking)</option>
                        <option value="SPOILED_TASTE">රස වෙනස් වීම (Spoiled Taste)</option>
                        <option value="OVERSTOCK">විකිණුනේ නැති අතිරික්ත (Overstock)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="font-bold" data-i18n="return_action_lbl">5. ලබාදෙන විසඳුම (Action):</label>
                    <select id="returnActionSelect" class="form-control">
                        <option value="CREDIT_NOTE">ණයෙන් අඩුකිරීම (Credit Note)</option>
                        <option value="REPLACEMENT">නොමිලේ අලුත් යෝගට් (Free Replacement)</option>
                        <option value="REFUND">මුදල් ආපසු (Cash Refund)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="font-bold">6. ආපසු ගත් ගණන (Cups):</label>
                    <input type="number" id="returnQtyInput" class="form-control font-bold text-center" value="10" min="1" style="font-size:1.2rem;" oninput="SalesRepWizard.calculateReturnTotal()">
                </div>

                <div class="form-group">
                    <label class="font-bold" data-i18n="return_total_val_lbl">කපාහරින මුළු මුදල (Rs.):</label>
                    <input type="text" id="returnTotalValueDisplay" class="form-control text-warning font-bold text-center" value="Rs. 800.00" style="font-size:1.2rem;" readonly>
                </div>
            </div>

            <div class="form-group mt-2">
                <label data-i18n="return_notes_lbl">අමතර සටහන් (Notes):</label>
                <input type="text" id="returnNotesInput" class="form-control" value="නරක් වූ යෝගට් කඩයෙන් ආපසු ලබාගන්නා ලදී.">
            </div>

            <button class="btn btn-warning btn-block font-bold mt-3 p-3" style="font-size:1rem;" onclick="SalesRepWizard.submitStockReturn()" data-i18n="modal_save_return">
                ✓ සටහන් කර ණයෙන් අඩුකරන්න (Confirm Return)
            </button>
        </div>
    </div>
</div>

<!-- MODAL 10: Crate & Container Tracking Modal -->
<div class="modal-overlay" id="crateTrackModal">
    <div class="modal-content-box" style="max-width: 500px;">
        <div class="modal-header">
            <h3 data-i18n="modal_crate_title">🧺 ප්ලාස්ටික් කූඩ සහ මුට්ටි බාරගැනීම (Crates & Pots)</h3>
            <button class="modal-close-btn" onclick="document.getElementById('crateTrackModal').style.display='none'">&times;</button>
        </div>
        <div class="modal-body">
            <p class="text-sm text-muted mb-3" data-i18n="modal_crate_sub">
                කඩයට ලබාදුන් සහ ආපසු එකතු කරගත් හිස් කූඩ සහ මීකිරි මුට්ටි ගණන් සටහන් කරන්න.
            </p>

            <div class="form-group">
                <label class="font-bold" data-i18n="modal_select_shop">කඩය තෝරන්න (Select Shop):</label>
                <select id="crateShopSelect" class="form-control font-bold" style="font-size:0.95rem;"></select>
            </div>

            <div class="card card-subtle p-3 my-2" style="border:1px solid rgba(72, 202, 228, 0.3);">
                <div class="font-bold text-xs text-info mb-2" data-i18n="crate_plastic_box_lbl">📦 ප්ලාස්ටික් කූඩ (Plastic Crates - 1 Crate = 30 Cups):</div>
                <div class="grid grid-2 gap-2" style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
                    <div>
                        <label class="text-xs font-bold" data-i18n="modal_crate_given">කඩේට දුන් කූඩ ගණන:</label>
                        <input type="number" id="cratesIssuedInput" class="form-control font-bold text-center" value="2" min="0" style="font-size:1.1rem;">
                    </div>
                    <div>
                        <label class="text-xs font-bold" data-i18n="modal_crate_returned">ආපසු ගත් හිස් කූඩ:</label>
                        <input type="number" id="cratesCollectedInput" class="form-control font-bold text-center" value="2" min="0" style="font-size:1.1rem;">
                    </div>
                </div>
            </div>

            <div class="card card-subtle p-3 my-2" style="border:1px solid rgba(255, 159, 28, 0.3);">
                <div class="font-bold text-xs text-warning mb-2" data-i18n="crate_clay_pots_lbl">🏺 මීකිරි මැටි මුට්ටි (Clay Curd Pots - Deposit Rs. 50):</div>
                <div class="grid grid-2 gap-2" style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
                    <div>
                        <label class="text-xs font-bold" data-i18n="modal_pots_given">දුන් මුට්ටි ගණන:</label>
                        <input type="number" id="potsIssuedInput" class="form-control font-bold text-center" value="0" min="0" style="font-size:1.1rem;">
                    </div>
                    <div>
                        <label class="text-xs font-bold" data-i18n="modal_pots_returned">ආපසු ගත් හිස් මුට්ටි:</label>
                        <input type="number" id="potsCollectedInput" class="form-control font-bold text-center" value="0" min="0" style="font-size:1.1rem;">
                    </div>
                </div>
            </div>

            <button class="btn btn-primary btn-block font-bold mt-3 p-3" style="font-size:1rem;" onclick="SalesRepWizard.submitCrateLog()" data-i18n="modal_save_crates">
                ✓ කූඩ ගණන් සටහන් කරන්න (Save Crate Record)
            </button>
        </div>
    </div>
</div>

<!-- MODAL 11: Shop Monthly Statement Ledger (Printable) -->
<div class="modal-overlay" id="shopStatementModal">
    <div class="modal-content-box" style="max-width: 680px;">
        <div class="modal-header">
            <h3 data-i18n="statement_modal_title">📑 Retail Shop Monthly Statement</h3>
            <button class="modal-close-btn" onclick="document.getElementById('shopStatementModal').style.display='none'">&times;</button>
        </div>
        <div class="modal-body">
            <div id="statementPrintArea" class="statement-print-paper p-3" style="background:#fff; color:#0F172A; border-radius:8px;">
                <!-- Statement Header -->
                <div class="statement-header flex-between" style="border-bottom: 2px solid #0F172A; padding-bottom: 8px;">
                    <div>
                        <h2 style="margin:0; font-size:1.3rem; color:#0F172A;">POLONNARUWA YOGURT CO.</h2>
                        <p style="margin:2px 0; font-size:0.75rem; color:#475569;">Central Depot &bull; Polonnaruwa Commercial Belt</p>
                        <p style="margin:0; font-size:0.75rem; color:#475569;">Hotline: 027-2224501 / 077-1234567</p>
                    </div>
                    <div class="text-right">
                        <span class="badge badge-primary font-bold" data-i18n="monthly_statement_badge">MONTHLY STATEMENT</span>
                        <div style="font-size:0.75rem; margin-top:4px; color:#475569;">Date: <strong id="stmtDate">2026-10-25</strong></div>
                    </div>
                </div>

                <!-- Shop Profile -->
                <div class="stmt-shop-box my-3 p-2" style="background:#F8FAFC; border:1px solid #E2E8F0; border-radius:6px; display:flex; justify-content:space-between; font-size:0.8rem;">
                    <div>
                        <div>Customer: <strong id="stmtShopName">--</strong></div>
                        <div>Owner: <span id="stmtOwnerName">--</span></div>
                        <div>Phone: <span id="stmtPhone">--</span></div>
                        <div>Route: <span id="stmtRouteName">--</span></div>
                    </div>
                    <div class="text-right">
                        <div>Credit Tier: <strong id="stmtTier" class="text-info">Tier A</strong></div>
                        <div>Credit Limit: <strong id="stmtCreditLimit">Rs. 0.00</strong></div>
                        <div class="mt-1" style="font-size:0.95rem;">Current Balance: <strong id="stmtOutstanding" style="color:#DC2626;">Rs. 0.00</strong></div>
                    </div>
                </div>

                <!-- Ledger Table -->
                <table class="table" style="width:100%; font-size:0.75rem; color:#0F172A; border-collapse:collapse;">
                    <thead>
                        <tr style="border-bottom: 2px solid #CBD5E1; background:#F1F5F9; text-align:left;">
                            <th style="padding:6px;">Date</th>
                            <th style="padding:6px;">Ref #</th>
                            <th style="padding:6px;">Description</th>
                            <th style="padding:6px; text-align:right;">Debit (Inv +)</th>
                            <th style="padding:6px; text-align:right;">Credit (Pay/Ret -)</th>
                            <th style="padding:6px; text-align:right;">Balance</th>
                        </tr>
                    </thead>
                    <tbody id="stmtLedgerTbody">
                        <!-- Populated by reportsView.js -->
                    </tbody>
                </table>

                <div class="stmt-footer-summary mt-3 pt-2" style="border-top:2px dashed #94A3B8; display:flex; justify-content:space-between; font-size:0.95rem; font-weight:bold;">
                    <span>NET OUTSTANDING CLOSING BALANCE:</span>
                    <span id="stmtFinalBalance" style="color:#DC2626;">Rs. 0.00</span>
                </div>
            </div>

            <div class="flex-between gap-2 mt-3">
                <button class="btn btn-secondary btn-block font-bold" onclick="window.print()" data-i18n="btn_print_statement">
                    🖨️ Print Statement / Save PDF
                </button>
                <button class="btn btn-primary btn-block font-bold" onclick="document.getElementById('shopStatementModal').style.display='none'" data-i18n="btn_done">
                    Done
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================
     MODAL 11: Field Credit Debt Collection (Sales Rep Mobile)
     ======================================================== -->
<div class="modal-overlay" id="fieldDebtPaymentModal">
    <div class="modal-content-box" style="max-width: 480px;">
        <div class="modal-header">
            <h3 data-i18n="modal_debt_title">💵 පරණ ණය මුදල් අයකරගැනීම</h3>
            <button class="modal-close-btn" onclick="SalesRepWizard.closeFieldDebtModal()">&times;</button>
        </div>
        <div class="modal-body">
            <div class="card card-subtle p-3 mb-3" style="background:rgba(239, 68, 68, 0.08); border:1px solid rgba(239,68,68,0.25);">
                <div class="flex-between">
                    <span class="text-xs text-muted" data-i18n="shop_lbl">වෙළඳසැල:</span>
                    <strong id="fieldDebtShopName" class="text-main">--</strong>
                </div>
                <div class="flex-between mt-1">
                    <span class="text-xs text-muted" data-i18n="phone_lbl">හිමිකරු / Phone:</span>
                    <span id="fieldDebtOwnerPhone" class="text-xs font-bold text-muted">--</span>
                </div>
                <div class="flex-between mt-2 pt-2" style="border-top:1px dashed rgba(255,255,255,0.1);">
                    <span class="text-sm font-bold text-danger" data-i18n="credit_outstanding">දැනට ඇති මුළු ණය:</span>
                    <strong class="text-danger font-bold text-lg" id="fieldDebtCurrentBalance">Rs. 0.00</strong>
                </div>
            </div>

            <!-- Quick Preset Amount Chips for Fast Touch -->
            <label class="font-bold text-xs text-muted mb-1 block">Quick Amount:</label>
            <div class="quick-debt-chips-grid mb-3" style="display:grid; grid-template-columns: repeat(3, 1fr); gap:8px;">
                <button type="button" class="btn btn-sm btn-outline-primary font-bold" onclick="SalesRepWizard.setDebtPreset(1000)">+ Rs. 1,000</button>
                <button type="button" class="btn btn-sm btn-outline-primary font-bold" onclick="SalesRepWizard.setDebtPreset(2000)">+ Rs. 2,000</button>
                <button type="button" class="btn btn-sm btn-outline-primary font-bold" onclick="SalesRepWizard.setDebtPreset(5000)">+ Rs. 5,000</button>
                <button type="button" class="btn btn-sm btn-outline-primary font-bold" onclick="SalesRepWizard.setDebtPreset(10000)">+ Rs. 10,000</button>
                <button type="button" class="btn btn-sm btn-outline-success font-bold" style="grid-column: span 2;" onclick="SalesRepWizard.setDebtFullAmount()">
                    ✓ Full Outstanding Amount
                </button>
            </div>

            <div class="form-group">
                <label class="font-bold" data-i18n="modal_debt_amount">අතට ලැබුණු මුදල (Amount Paid - Rs.):</label>
                <input type="number" id="fieldDebtAmountInput" class="form-control font-bold text-center text-success" style="font-size:1.4rem; height:54px;" placeholder="0.00" min="1" step="100">
            </div>

            <div class="form-group">
                <label class="text-xs text-muted">Notes / Memo:</label>
                <input type="text" id="fieldDebtNotesInput" class="form-control" placeholder="Optional payment note">
            </div>

            <button type="button" class="btn btn-success btn-block font-bold mt-3" onclick="SalesRepWizard.submitFieldDebtPayment()" style="min-height:52px; font-size:1.15rem;" data-i18n="modal_collect_btn">
                💵 මුදල් ලැබුණා - රිසිට්පත දෙන්න (Confirm Payment)
            </button>
        </div>
    </div>
</div>

<!-- ========================================================
     MODAL 12: Field Debt Payment Slip & WhatsApp Modal
     ======================================================== -->
<div class="modal-overlay" id="fieldDebtReceiptModal">
    <div class="modal-content-box" style="max-width: 440px;">
        <div class="modal-header">
            <h3 data-i18n="modal_debt_receipt_title">🧾 ණය ගෙවීමේ රිසිට්පත</h3>
            <button class="modal-close-btn" onclick="SalesRepWizard.closeFieldDebtReceiptModal()">&times;</button>
        </div>
        <div class="modal-body">
            <div class="receipt-paper" id="printableDebtReceipt">
                <div class="receipt-header-center">
                    <h3 style="margin: 0; font-size: 1.1rem;">POLONNARUWA YOGURT CO.</h3>
                    <p style="font-size: 0.72rem; margin: 2px 0;" data-i18n="debt_receipt_sub">ණය අයකරගැනීමේ රිසිට්පත (Debt Collection Receipt)</p>
                    <p style="font-size: 0.72rem; margin: 0;">Tel: 027-2224501 / 077-1234567</p>
                </div>

                <div style="font-size: 0.78rem; display: flex; justify-content: space-between; margin: 8px 0 6px;">
                    <span><span data-i18n="debt_receipt_no_lbl">රිසිට් අංකය:</span> <strong id="debtReceiptNo">--</strong></span>
                    <span id="debtReceiptDate">--</span>
                </div>

                <div style="font-size: 0.78rem; border-top:1px dashed #CBD5E1; border-bottom: 1px dashed #CBD5E1; padding: 6px 0; margin-bottom: 8px;">
                    <div><span data-i18n="customer_shop">වෙළඳසැල:</span> <strong id="debtReceiptShopName">--</strong></div>
                    <div><span data-i18n="modal_owner_name">හිමිකරු:</span> <span id="debtReceiptOwnerName">--</span></div>
                    <div><span data-i18n="modal_phone">දුරකථන:</span> <span id="debtReceiptPhone">--</span></div>
                    <div><span data-i18n="debt_receipt_officer">අයකරගත් නිලධාරී:</span> <span id="debtReceiptRepName">සේල්ස් රෙප්</span></div>
                </div>

                <div style="font-size: 0.85rem; line-height: 1.8;">
                    <div class="flex-between">
                        <span data-i18n="debt_prev_bal">කලින් තිබූ ණය මුදල:</span>
                        <span id="debtReceiptPrevBalance" style="color:#64748B;">Rs. 0.00</span>
                    </div>
                    <div class="flex-between font-bold" style="font-size: 1.05rem; color:#16A34A; border-top:1px solid #CBD5E1; border-bottom:1px solid #CBD5E1; padding:4px 0; margin:4px 0;">
                        <span data-i18n="debt_paid_amt">අද ගෙවූ මුදල (CASH):</span>
                        <span id="debtReceiptPaidAmount">Rs. 0.00</span>
                    </div>
                    <div class="flex-between font-bold" style="font-size: 0.95rem;">
                        <span data-i18n="debt_new_bal">ඉතිරි ණය මුදල:</span>
                        <span id="debtReceiptNewBalance" style="color:#DC2626;">Rs. 0.00</span>
                    </div>
                </div>

                <div class="receipt-seal-box mt-3">
                    <div style="font-weight: bold; font-size: 0.65rem; color: #475569;" data-i18n="debt_verified_seal">
                        ✓ පද්ධතියේ සටහන් වූ නිල රිසිට්පතයි (Verified Digital Receipt)
                    </div>
                </div>
            </div>

            <div class="mt-3">
                <button type="button" class="btn btn-whatsapp btn-block font-bold mb-2" onclick="SalesRepWizard.shareDebtReceiptViaWhatsApp()" style="background:#25D366; color:white; font-size:1.05rem; padding:12px; border-radius:8px; display:flex; align-items:center; justify-content:center; gap:8px; border:none; cursor:pointer;" data-i18n="btn_share_whatsapp_receipt">
                    <span style="font-size:1.25rem;">📲</span> WhatsApp මගින් රිසිට්පත යවන්න
                </button>
            </div>

            <div class="flex-between gap-2">
                <button class="btn btn-secondary btn-block font-bold" onclick="window.print()" data-i18n="btn_print_slip">
                    🖨️ Print Slip
                </button>
                <button class="btn btn-primary btn-block font-bold" onclick="SalesRepWizard.closeFieldDebtReceiptModal()" data-i18n="btn_done">
                    Done
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================
     MODAL 13: Add New Shop on Route (Field Registration)
     ======================================================== -->
<div class="modal-overlay" id="newShopModal">
    <div class="modal-content-box" style="max-width: 500px;">
        <div class="modal-header">
            <h3 data-i18n="modal_new_shop_title">🏪 අලුත් කඩයක් ලියාපදිංචි කිරීම</h3>
            <button class="modal-close-btn" onclick="SalesRepWizard.closeNewShopModal()">&times;</button>
        </div>
        <div class="modal-body">
            <p class="text-sm text-muted mb-3" data-i18n="modal_new_shop_sub">
                බෙදාහරින අතරමගදී හමුවන අලුත් වෙළඳසැල් ක්ෂණිකව පද්ධතියට එක්කර එවලේම බිල දමන්න.
            </p>

            <div class="form-group">
                <label class="font-bold" data-i18n="modal_shop_name">1. වෙළඳසැලේ නම (Shop Name): <span class="text-danger">*</span></label>
                <input type="text" id="newShopNameInput" class="form-control font-bold" placeholder="E.g. Sirisena Stores" style="font-size:1rem;">
            </div>

            <div class="grid grid-2 gap-2" style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
                <div class="form-group">
                    <label class="font-bold" data-i18n="modal_owner_name">2. හිමිකරු (Owner): <span class="text-danger">*</span></label>
                    <input type="text" id="newShopOwnerInput" class="form-control" placeholder="E.g. D. Sirisena">
                </div>
                <div class="form-group">
                    <label class="font-bold" data-i18n="modal_phone">3. දුරකථන (Phone): <span class="text-danger">*</span></label>
                    <input type="text" id="newShopPhoneInput" class="form-control font-mono font-bold" placeholder="0771234567">
                </div>
            </div>

            <div class="form-group">
                <label class="font-bold" data-i18n="modal_new_shop_address">4. Address & Landmark:</label>
                <input type="text" id="newShopAddressInput" class="form-control" placeholder="E.g. Main Street, Medirigiriya">
            </div>

            <div class="form-group">
                <label class="font-bold" data-i18n="modal_new_shop_tier">5. Credit Tier:</label>
                <select id="newShopCreditTierSelect" class="form-control">
                    <option value="Tier C">Tier C - Rs. 15,000 (Initial Credit Limit)</option>
                    <option value="Cash Only">Cash Only (No Credit Allowed)</option>
                    <option value="Tier B">Tier B - Rs. 35,000 (Verified Regular)</option>
                </select>
            </div>

            <button type="button" class="btn btn-primary btn-block font-bold mt-3" onclick="SalesRepWizard.submitNewShopRegistration()" style="min-height:52px; font-size:1.15rem;" data-i18n="modal_save_shop">
                💾 කඩය ලියාපදිංචි කර තෝරන්න (Save & Select)
            </button>
        </div>
    </div>
</div>

<!-- ========================================================
     MODAL 14: Register Fleet Vehicle (Cold Van / Chilled Truck)
     ======================================================== -->
<div class="modal-overlay" id="registerVehicleModal">
    <div class="modal-content-box" style="max-width: 520px;">
        <div class="modal-header">
            <h3>🚐 Register New Fleet Vehicle</h3>
            <button class="modal-close-btn" onclick="AdminDashboard.closeRegisterVehicleModal()">&times;</button>
        </div>
        <div class="modal-body">
            <p class="text-sm text-muted mb-3">
                Add a new refrigerated truck, van, or cold hauler to the Polonnaruwa dairy distribution fleet.
            </p>

            <div class="form-group">
                <label class="font-bold">1. Vehicle Registration Plate No: <span class="text-danger">*</span></label>
                <input type="text" id="newVehicleNumber" class="form-control font-mono font-bold" placeholder="E.g. NC-GA-9944 / WP-DA-5621" style="font-size:1.05rem; letter-spacing:0.5px;">
            </div>

            <div class="grid grid-2 gap-2" style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
                <div class="form-group">
                    <label class="font-bold">2. Vehicle Type:</label>
                    <select id="newVehicleType" class="form-control">
                        <option value="Refrigerated Van">Refrigerated Van (Cold Van)</option>
                        <option value="Chilled Light Truck">Chilled Light Truck</option>
                        <option value="Heavy Cold Hauler">Heavy Cold Hauler</option>
                        <option value="Standby Insulated Van">Standby Insulated Van</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="font-bold">3. Capacity (Cups): <span class="text-danger">*</span></label>
                    <input type="number" id="newVehicleCapacity" class="form-control font-mono font-bold" value="2500" min="100" step="100">
                </div>
            </div>

            <div class="grid grid-2 gap-2" style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
                <div class="form-group">
                    <label class="font-bold">4. Cooling System:</label>
                    <select id="newVehicleCooling" class="form-control">
                        <option value="NORMAL_COOLING">Active Chiller (2°C - 4°C Optimal)</option>
                        <option value="HEAVY_CHILLER">Heavy Duty Deep Chill</option>
                        <option value="INSULATED_ONLY">Passive Cold Eutectic Plates</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="font-bold">5. Initial Fleet Status:</label>
                    <select id="newVehicleStatus" class="form-control">
                        <option value="AVAILABLE">AVAILABLE (Depot Standby)</option>
                        <option value="IN_TRANSIT">IN_TRANSIT (On Delivery Route)</option>
                        <option value="MAINTENANCE">MAINTENANCE (Workshop Service)</option>
                    </select>
                </div>
            </div>

            <button type="button" class="btn btn-primary btn-block font-bold mt-3" onclick="AdminDashboard.submitRegisterVehicle()" style="min-height:50px; font-size:1.1rem;">
                💾 Save Vehicle to Fleet
            </button>
        </div>
    </div>
</div>

<!-- ========================================================
     MODAL 15: Register Fleet Driver
     ======================================================== -->
<div class="modal-overlay" id="registerDriverModal">
    <div class="modal-content-box" style="max-width: 540px;">
        <div class="modal-header">
            <h3>🚚 Register New Fleet Driver</h3>
            <button class="modal-close-btn" onclick="AdminDashboard.closeRegisterDriverModal()">&times;</button>
        </div>
        <div class="modal-body">
            <p class="text-sm text-muted mb-3">
                Register a verified commercial driver for daily yogurt delivery routes.
            </p>

            <div class="form-group">
                <label class="font-bold">1. Driver Full Name: <span class="text-danger">*</span></label>
                <input type="text" id="newDriverName" class="form-control font-bold" placeholder="E.g. Kamal Perera / Sunil Rathnayake" style="font-size:1rem;">
            </div>

            <div class="grid grid-2 gap-2" style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
                <div class="form-group">
                    <label class="font-bold">2. NIC Number:</label>
                    <input type="text" id="newDriverNic" class="form-control font-mono" placeholder="E.g. 198812345678">
                </div>
                <div class="form-group">
                    <label class="font-bold">3. Driving License No:</label>
                    <input type="text" id="newDriverLicense" class="form-control font-mono" placeholder="E.g. B-7821943">
                </div>
            </div>

            <div class="grid grid-2 gap-2" style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
                <div class="form-group">
                    <label class="font-bold">4. Mobile Phone: <span class="text-danger">*</span></label>
                    <input type="text" id="newDriverPhone" class="form-control font-mono font-bold" placeholder="0771234567">
                </div>
                <div class="form-group">
                    <label class="font-bold">5. Assigned Vehicle:</label>
                    <select id="newDriverVehicleSelect" class="form-control">
                        <option value="">-- No Vehicle Assigned (Spare) --</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="font-bold">6. Operational Status:</label>
                <select id="newDriverStatus" class="form-control">
                    <option value="ACTIVE">ACTIVE (Available for Dispatch)</option>
                    <option value="ON_TRIP">ON_TRIP (Currently on Route)</option>
                    <option value="OFF_DUTY">OFF_DUTY (Leave / Off Roster)</option>
                </select>
            </div>

            <!-- Optional Driver Login Provisioning -->
            <div class="card p-3 my-2" style="background:var(--bg-surface-elevated); border:1px solid var(--border-medium); border-radius:var(--radius-md);">
                <div class="flex-between mb-2">
                    <label class="font-bold text-xs" style="cursor:pointer;">
                        <input type="checkbox" id="provisionDriverAccountCheck" onchange="document.getElementById('driverAccountFields').style.display = this.checked ? 'grid' : 'none'">
                        📱 Create Driver App Login Account
                    </label>
                </div>
                <div id="driverAccountFields" style="display:none; grid-template-columns:1fr 1fr; gap:10px;">
                    <div class="form-group mb-0">
                        <label class="text-xs">Driver Email:</label>
                        <input type="email" id="newDriverEmail" class="form-control text-xs" placeholder="driver@pulse.lk">
                    </div>
                    <div class="form-group mb-0">
                        <label class="text-xs">Default Password:</label>
                        <input type="password" id="newDriverPassword" class="form-control text-xs" value="password">
                    </div>
                </div>
            </div>

            <button type="button" class="btn btn-primary btn-block font-bold mt-3" onclick="AdminDashboard.submitRegisterDriver()" style="min-height:50px; font-size:1.1rem;">
                💾 Register Driver & Save
            </button>
        </div>
    </div>
</div>

<!-- ========================================================
     MODAL 16: Register Sales Representative
     ======================================================== -->
<div class="modal-overlay" id="registerSalesRepModal">
    <div class="modal-content-box" style="max-width: 540px;">
        <div class="modal-header">
            <h3>📱 Register Sales Representative</h3>
            <button class="modal-close-btn" onclick="AdminDashboard.closeRegisterSalesRepModal()">&times;</button>
        </div>
        <div class="modal-body">
            <p class="text-sm text-muted mb-3">
                Register a field sales representative to take shop orders, collect cash, and manage crate returns.
            </p>

            <div class="form-group">
                <label class="font-bold">1. Representative Full Name: <span class="text-danger">*</span></label>
                <input type="text" id="newRepName" class="form-control font-bold" placeholder="E.g. Sunil Shantha / Malith Fernando" style="font-size:1rem;">
            </div>

            <div class="grid grid-2 gap-2" style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
                <div class="form-group">
                    <label class="font-bold">2. Employee Code / ID: <span class="text-danger">*</span></label>
                    <input type="text" id="newRepCode" class="form-control font-mono font-bold" placeholder="REP-005">
                </div>
                <div class="form-group">
                    <label class="font-bold">3. Mobile Phone: <span class="text-danger">*</span></label>
                    <input type="text" id="newRepPhone" class="form-control font-mono font-bold" placeholder="0719876543">
                </div>
            </div>

            <div class="grid grid-2 gap-2" style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
                <div class="form-group">
                    <label class="font-bold">4. Assigned Route Sector:</label>
                    <select id="newRepSector" class="form-control">
                        <option value="Kaduruwela Market Sector">Kaduruwela Market Sector</option>
                        <option value="Hingurakgoda Route">Hingurakgoda Route</option>
                        <option value="Minneriya Dairy Hub">Minneriya Dairy Hub</option>
                        <option value="Medirigiriya Dairy Belt">Medirigiriya Dairy Belt</option>
                        <option value="Dimbulagala Sector">Dimbulagala Sector</option>
                        <option value="Polonnaruwa Central">Polonnaruwa Central</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="font-bold">5. Commission Rate (%):</label>
                    <input type="number" id="newRepCommission" class="form-control font-mono" value="3.5" step="0.1" min="0" max="25">
                </div>
            </div>

            <div class="form-group">
                <label class="font-bold">6. Status:</label>
                <select id="newRepStatus" class="form-control">
                    <option value="ACTIVE">ACTIVE (Field Duty)</option>
                    <option value="INACTIVE">INACTIVE</option>
                    <option value="ON_LEAVE">ON_LEAVE</option>
                </select>
            </div>

            <!-- Optional Rep Login Provisioning -->
            <div class="card p-3 my-2" style="background:var(--bg-surface-elevated); border:1px solid var(--border-medium); border-radius:var(--radius-md);">
                <div class="flex-between mb-2">
                    <label class="font-bold text-xs" style="cursor:pointer;">
                        <input type="checkbox" id="provisionRepAccountCheck" onchange="document.getElementById('repAccountFields').style.display = this.checked ? 'grid' : 'none'">
                        📱 Create Sales Rep App Login Account
                    </label>
                </div>
                <div id="repAccountFields" style="display:none; grid-template-columns:1fr 1fr; gap:10px;">
                    <div class="form-group mb-0">
                        <label class="text-xs">Rep Email:</label>
                        <input type="email" id="newRepEmail" class="form-control text-xs" placeholder="rep@pulse.lk">
                    </div>
                    <div class="form-group mb-0">
                        <label class="text-xs">Default Password:</label>
                        <input type="password" id="newRepPassword" class="form-control text-xs" value="password">
                    </div>
                </div>
            </div>

            <button type="button" class="btn btn-primary btn-block font-bold mt-3" onclick="AdminDashboard.submitRegisterSalesRep()" style="min-height:50px; font-size:1.1rem;">
                💾 Register Sales Rep & Save
            </button>
        </div>
    </div>
</div>

<!-- ========================================================
     MODAL 17: Register Delivery Route (Sector & Region)
     ======================================================== -->
<div class="modal-overlay" id="registerRouteModal">
    <div class="modal-content-box" style="max-width: 520px;">
        <div class="modal-header">
            <h3 id="registerRouteModalTitle">🗺️ Register New Delivery Route</h3>
            <button class="modal-close-btn" onclick="AdminDashboard.closeRegisterRouteModal()">&times;</button>
        </div>
        <div class="modal-body">
            <p class="text-sm text-muted mb-3">
                Define a new regional distribution corridor and sector for dairy vehicle routing and retail shops.
            </p>

            <div class="form-group">
                <label class="font-bold">1. Route Name / Corridor Code: <span class="text-danger">*</span></label>
                <input type="text" id="newRouteName" class="form-control font-bold" placeholder="E.g. Route 05 - Giritale & Elahera Belt" style="font-size:1rem;">
            </div>

            <div class="grid grid-2 gap-2" style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
                <div class="form-group">
                    <label class="font-bold">2. Region / Sector: <span class="text-danger">*</span></label>
                    <input type="text" id="newRouteRegion" class="form-control font-bold" placeholder="Giritale & Elahera">
                </div>
                <div class="form-group">
                    <label class="font-bold">3. Estimated Distance (KM):</label>
                    <input type="number" id="newRouteDistance" class="form-control font-mono font-bold" value="35.0" step="0.5" min="1">
                </div>
            </div>

            <div class="form-group">
                <label class="font-bold">4. Primary Destination / Terminal Junction:</label>
                <input type="text" id="newRouteDestination" class="form-control" placeholder="E.g. Giritale Junction Market">
            </div>

            <div class="grid grid-2 gap-2" style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
                <div class="form-group">
                    <label class="font-bold">5. Assigned Vehicle (Optional):</label>
                    <select id="newRouteVehicle" class="form-control">
                        <option value="">-- No Vehicle Assigned --</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="font-bold">6. Status:</label>
                    <select id="newRouteStatus" class="form-control">
                        <option value="ACTIVE">ACTIVE</option>
                        <option value="INACTIVE">INACTIVE</option>
                    </select>
                </div>
            </div>

            <button type="button" class="btn btn-primary btn-block font-bold mt-3" onclick="AdminDashboard.submitRegisterRoute()" style="min-height:50px; font-size:1.1rem;">
                💾 Save Route Corridor
            </button>
        </div>
    </div>
</div>





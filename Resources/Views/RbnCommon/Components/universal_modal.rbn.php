<!-- Rbn Universal Modal Shell 🪟 -->
<div class="modal rbn-universal-modal fade" id="universalRbnModal" tabindex="-1" aria-hidden="true"
    data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rbn-modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <!-- JS will inject title here -->
                    Yükleniyor...
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" data-rbn-dismiss="modal" aria-label="Kapat"></button>
            </div>

            <div class="modal-body rbn-modal-body p-0" id="universalRbnModalBody">
                <!-- JS will inject AJAX content here -->
                <div class="text-center p-5">
                    <div class="spinner-border text-primary" role="status"></div>
                </div>
            </div>

            <!-- Modal Footer is usually part of the AJAX content OR dynamic -->
            <div class="modal-footer d-none">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" data-rbn-dismiss="modal">Kapat</button>
                <button type="button" class="btn btn-primary" id="modalConfirmBtn">Tamam</button>
            </div>
        </div>
    </div>
</div>

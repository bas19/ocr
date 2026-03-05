<script setup>
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    receipts: Object,
});

const selectedReceipt = ref(null);
const showModal = ref(false);
const deleting = ref(null);

const hasReceipts = computed(() => props.receipts.data.length > 0);

const formatDate = (date) => {
    if (!date) return 'N/A';
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

const formatLongDate = (date) => {
    if (!date) return 'N/A';
    return new Date(date).toLocaleDateString('en-US', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};

const viewReceipt = (receipt) => {
    selectedReceipt.value = receipt;
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    selectedReceipt.value = null;
};

const deleteReceipt = (id) => {
    if (!confirm('Are you sure you want to delete this receipt?')) {
        return;
    }

    deleting.value = id;
    router.delete(`/api/receipts/${id}`, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            deleting.value = null;
        },
        onError: () => {
            deleting.value = null;
            alert('Failed to delete receipt');
        },
    });
};

const getStatusBadgeClass = (status) => {
    switch (status) {
        case 'processed':
            return 'bg-success';
        case 'pending':
            return 'bg-warning';
        case 'failed':
            return 'bg-danger';
        default:
            return 'bg-secondary';
    }
};
</script>

<template>
    <Head title="All Receipts" />

    <div class="container-fluid bg-light min-vh-100 py-5">
        <div class="container">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-start mb-5">
                <div>
                    <h1 class="display-4 fw-bold mb-2">All Receipts</h1>
                    <p class="lead text-muted">Manage your scanned receipts</p>
                </div>
                <a href="/receipts/upload" class="btn btn-primary btn-lg">
                    <svg class="me-2" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Upload Receipt
                </a>
            </div>

            <!-- Empty State -->
            <div v-if="!hasReceipts" class="card shadow-lg text-center p-5">
                <div class="card-body py-5">
                    <svg class="mx-auto mb-4" width="80" height="80" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: #dee2e6;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <h3 class="h4 mb-3">No receipts yet</h3>
                    <p class="text-muted mb-4">Start by uploading your first receipt</p>
                    <a href="/receipts/upload" class="btn btn-primary">
                        <svg class="me-2" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Upload Receipt
                    </a>
                </div>
            </div>

            <!-- Receipts Grid -->
            <div v-else class="row g-4">
                <div
                    v-for="receipt in receipts.data"
                    :key="receipt.id"
                    class="col-md-6 col-lg-4"
                >
                    <div class="card h-100 shadow-lg">
                        <div class="card-body">
                            <!-- Status Badge -->
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <span :class="getStatusBadgeClass(receipt.status)" class="badge">
                                    {{ receipt.status }}
                                </span>
                            </div>

                            <!-- Invoice Number -->
                            <div class="mb-3">
                                <label class="text-muted small d-block mb-1">Invoice Number</label>
                                <div class="h5 fw-bold text-dark mb-0">
                                    {{ receipt.invoice_number || 'Not found' }}
                                </div>
                            </div>

                            <!-- Supplier -->
                            <div class="mb-3">
                                <label class="text-muted small d-block mb-1">Supplier</label>
                                <div class="fw-semibold text-truncate">
                                    {{ receipt.supplier || 'Not found' }}
                                </div>
                            </div>

                            <!-- Date -->
                            <div class="mb-3">
                                <label class="text-muted small d-block mb-1">Date</label>
                                <div class="text-muted">
                                    {{ formatDate(receipt.transaction_date) }}
                                </div>
                            </div>

                            <!-- Total -->
                            <div class="mb-3">
                                <label class="text-muted small d-block mb-1">Total</label>
                                <div class="h5 fw-bold text-success mb-0">
                                    {{ receipt.total_amount ? `$${parseFloat(receipt.total_amount).toFixed(2)}` : 'Not found' }}
                                </div>
                            </div>

                            <!-- Description -->
                            <div v-if="receipt.description" class="mb-3">
                                <label class="text-muted small d-block mb-1">Description</label>
                                <div class="text-muted small text-truncate">
                                    {{ receipt.description }}
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="d-flex gap-2 pt-3 border-top">
                                <button
                                    @click="viewReceipt(receipt)"
                                    class="btn btn-outline-primary btn-sm flex-fill"
                                >
                                    View Details
                                </button>
                                <button
                                    @click="deleteReceipt(receipt.id)"
                                    :disabled="deleting === receipt.id"
                                    class="btn btn-outline-danger btn-sm"
                                >
                                    <span v-if="deleting === receipt.id">
                                        <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                                    </span>
                                    <span v-else>Delete</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="hasReceipts && receipts.last_page > 1" class="mt-5 d-flex justify-content-center">
                <nav>
                    <ul class="pagination">
                        <li
                            v-for="page in receipts.last_page"
                            :key="page"
                            :class="{ 'active': page === receipts.current_page }"
                            class="page-item"
                        >
                            <button
                                @click="router.get(`/receipts?page=${page}`)"
                                class="page-link"
                            >
                                {{ page }}
                            </button>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <Teleport to="body">
        <div
            v-if="showModal && selectedReceipt"
            class="modal fade show d-block"
            tabindex="-1"
            style="background-color: rgba(0,0,0,0.5);"
            @click.self="closeModal"
        >
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Receipt Details</h5>
                        <button
                            type="button"
                            class="btn-close"
                            @click="closeModal"
                            aria-label="Close"
                        ></button>
                    </div>
                    <div class="modal-body">
                        <!-- Status -->
                        <div class="mb-4">
                            <span :class="getStatusBadgeClass(selectedReceipt.status)" class="badge fs-6 px-3 py-2">
                                {{ selectedReceipt.status.charAt(0).toUpperCase() + selectedReceipt.status.slice(1) }}
                            </span>
                        </div>

                        <!-- Extracted Information -->
                        <div class="card bg-light mb-4">
                            <div class="card-body">
                                <!-- Invoice Number -->
                                <div class="mb-3 pb-3 border-bottom">
                                    <label class="text-muted small d-block mb-1">Invoice Number</label>
                                    <div class="h5 fw-bold mb-0">
                                        {{ selectedReceipt.invoice_number || 'Not found' }}
                                    </div>
                                </div>

                                <!-- Supplier -->
                                <div class="mb-3 pb-3 border-bottom">
                                    <label class="text-muted small d-block mb-1">Supplier</label>
                                    <div class="h5 fw-bold mb-0">
                                        {{ selectedReceipt.supplier || 'Not found' }}
                                    </div>
                                </div>

                                <!-- Date -->
                                <div>
                                    <label class="text-muted small d-block mb-1">Transaction Date</label>
                                    <div class="h5 fw-bold mb-0">
                                        {{ formatLongDate(selectedReceipt.transaction_date) }}
                                    </div>
                                </div>

                                <!-- Total -->
                                <div class="mt-3 pt-3 border-top">
                                    <label class="text-muted small d-block mb-1">Total Amount</label>
                                    <div class="h4 fw-bold text-success mb-0">
                                        {{ selectedReceipt.total_amount ? `$${parseFloat(selectedReceipt.total_amount).toFixed(2)}` : 'Not found' }}
                                    </div>
                                </div>

                                <!-- Description -->
                                <div v-if="selectedReceipt.description" class="mt-3 pt-3 border-top">
                                    <label class="text-muted small d-block mb-2">Description</label>
                                    <div class="text-muted">
                                        {{ selectedReceipt.description }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Raw Text -->
                        <div v-if="selectedReceipt.raw_text">
                            <details>
                                <summary class="fw-semibold mb-2" style="cursor: pointer;">
                                    Raw OCR Text
                                </summary>
                                <div class="bg-dark text-light rounded p-3 small font-monospace" style="max-height: 300px; overflow-y: auto; white-space: pre-wrap;">{{ selectedReceipt.raw_text }}</div>
                            </details>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button
                            type="button"
                            class="btn btn-secondary"
                            @click="closeModal"
                        >
                            Close
                        </button>
                        <button
                            type="button"
                            class="btn btn-danger"
                            @click="deleteReceipt(selectedReceipt.id); closeModal()"
                        >
                            Delete
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>

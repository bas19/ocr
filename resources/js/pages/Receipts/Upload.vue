<script setup>
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';

const props = defineProps({
    receiptId: [Number, String],
});

console.log('=== Upload Component Initialized ===');
console.log('Props receiptId:', props.receiptId);

const page = usePage();

const form = useForm({
    image: null,
});

const previewUrl = ref(null);
const result = ref(null);
const successMessage = ref(null);
const isLoadingReceipt = ref(false);

const errors = computed(() => page.props.errors || {});

// Fetch receipt data from API using native fetch
const fetchReceipt = async (receiptId) => {
    if (!receiptId) {
        console.log('No receiptId provided');
        return;
    }

    console.log('=== Fetching Receipt ===');
    console.log('Receipt ID:', receiptId);

    isLoadingReceipt.value = true;
    try {
        const url = `/api/receipts/${receiptId}`;
        console.log('Fetching from:', url);

        const response = await fetch(url, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();
        console.log('Receipt data received:', data);

        result.value = data;
        successMessage.value = 'Receipt processed successfully!';
        console.log('Result set to:', result.value);
    } catch (error) {
        console.error('Failed to fetch receipt:', error);
        console.error('Error message:', error.message);
    } finally {
        isLoadingReceipt.value = false;
        console.log('Loading complete');
    }
};

// Watch for receiptId changes (when receipt is processed)
watch(() => props.receiptId, (newReceiptId, oldReceiptId) => {
    console.log('=== ReceiptId Changed ===');
    console.log('Old receiptId:', oldReceiptId);
    console.log('New receiptId:', newReceiptId);

    if (newReceiptId) {
        fetchReceipt(newReceiptId);
    }
}, { immediate: true });

// Watch for success message in flash
watch(() => page.props.flash?.message, (message) => {
    if (message) {
        successMessage.value = message;
    }
}, { immediate: true });

// Watch all page props for debugging
watch(() => page.props, (newProps) => {
    console.log('=== Page Props Changed ===');
    console.log('All props:', newProps);
}, { deep: true });

// Watch result changes
watch(result, (newResult, oldResult) => {
    console.log('=== Result Changed ===');
    console.log('Old result:', oldResult);
    console.log('New result:', newResult);
    console.log('Result is truthy:', !!newResult);
}, { deep: true });

// Watch loading state changes
watch(isLoadingReceipt, (newValue, oldValue) => {
    console.log('=== isLoadingReceipt Changed ===');
    console.log('Old:', oldValue, '-> New:', newValue);
});

const handleFileChange = (event) => {
    const file = event.target.files?.[0];

    if (file) {
        form.image = file;

        const reader = new FileReader();
        reader.onload = (e) => {
            previewUrl.value = e.target?.result;
        };
        reader.readAsDataURL(file);

        result.value = null;
        successMessage.value = null;
    }
};

const handleDrop = (event) => {
    event.preventDefault();
    const file = event.dataTransfer?.files[0];

    if (file && file.type.startsWith('image/')) {
        form.image = file;

        const reader = new FileReader();
        reader.onload = (e) => {
            previewUrl.value = e.target?.result;
        };
        reader.readAsDataURL(file);

        result.value = null;
        successMessage.value = null;
    }
};

const handleDragOver = (event) => {
    event.preventDefault();
};

const submitForm = () => {
    if (!form.image) return;

    console.log('=== Submitting Form ===');

    form.post('/receipts', {
        preserveScroll: true,
        forceFormData: true, // Ensure multipart/form-data for file upload
        onSuccess: (page) => {
            console.log('=== Form Submission Success ===');
            console.log('Response page:', page);

            // Clear the preview to show the results section
            previewUrl.value = null;
            form.reset();

            // Receipt data will be fetched via API after redirect
            console.log('Receipt uploaded successfully, preview cleared');
        },
        onError: (errors) => {
            console.error('=== Form Submission Errors ===');
            console.error('Errors:', errors);
        },
    });
};

const clearForm = () => {
    console.log('=== Clearing Form ===');
    form.reset();
    previewUrl.value = null;
    result.value = null;
    successMessage.value = null;
    console.log('Form cleared');
};
</script>

<template>
    <Head title="Upload Receipt" />

    <div class="min-vh-100" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
        <div class="container py-5">
            <!-- Header -->
            <div class="text-center mb-5 text-white">
                <div class="mb-3">
                    <svg width="64" height="64" fill="currentColor" viewBox="0 0 24 24" class="mx-auto">
                        <path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                    </svg>
                </div>
                <h1 class="display-4 fw-bold mb-2">Receipt OCR Scanner</h1>
                <p class="lead opacity-90">Extract invoice numbers and dates from your receipts instantly</p>
            </div>

            <div class="row g-4 mb-4">
                <!-- Upload Section -->
                <div class="col-lg-6">
                    <div class="card border-0 shadow-lg h-100" style="border-radius: 20px;">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center mb-4">
                                <div class="bg-primary bg-opacity-10 rounded-circle p-2 me-3">
                                    <svg width="24" height="24" fill="currentColor" viewBox="0 0 24 24" class="text-primary">
                                        <path d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                                    </svg>
                                </div>
                                <h2 class="h5 mb-0 fw-bold">Upload Receipt</h2>
                            </div>

                            <!-- Drag & Drop Area -->
                            <div
                                v-if="!previewUrl"
                                @drop="handleDrop"
                                @dragover="handleDragOver"
                                class="border-2 border-dashed rounded-3 p-5 text-center transition-all"
                                style="border-color: #dee2e6; cursor: pointer; transition: all 0.3s;"
                                @mouseenter="$event.currentTarget.style.borderColor = '#667eea'"
                                @mouseleave="$event.currentTarget.style.borderColor = '#dee2e6'"
                            >
                                <div class="mb-4">
                                    <svg
                                        width="80"
                                        height="80"
                                        stroke="currentColor"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        style="color: #667eea;"
                                        class="mx-auto"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="1.5"
                                            d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"
                                        />
                                    </svg>
                                </div>
                                <div class="mb-3">
                                    <label for="file-upload" class="btn btn-primary btn-lg px-4 shadow-sm">
                                        <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24" class="me-2">
                                            <path d="M12 4v16m8-8H4" stroke="currentColor" stroke-width="2" stroke-linecap="round" fill="none"/>
                                        </svg>
                                        Choose File
                                        <input
                                            id="file-upload"
                                            type="file"
                                            class="d-none"
                                            accept="image/*"
                                            @change="handleFileChange"
                                        />
                                    </label>
                                </div>
                                <p class="text-muted mb-1">or drag and drop your receipt here</p>
                                <p class="text-muted small mb-0">PNG, JPG, GIF, WebP • Max 10MB</p>
                            </div>

                            <!-- Preview -->
                            <div v-else>
                                <div class="mb-3 position-relative">
                                    <img
                                        :src="previewUrl"
                                        alt="Receipt preview"
                                        class="img-fluid rounded-3 shadow-sm w-100"
                                        style="max-height: 400px; object-fit: contain; background: #f8f9fa;"
                                    />
                                    <button
                                        @click="clearForm"
                                        :disabled="form.processing"
                                        class="btn btn-sm btn-light position-absolute top-0 end-0 m-2 shadow-sm"
                                        style="border-radius: 50%; width: 36px; height: 36px; padding: 0;"
                                    >
                                        <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M6 18L18 6M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                        </svg>
                                    </button>
                                </div>

                                <button
                                    @click="submitForm"
                                    :disabled="form.processing"
                                    class="btn btn-primary btn-lg w-100 shadow-sm"
                                >
                                    <span v-if="form.processing">
                                        <span class="spinner-border spinner-border-sm me-2" role="status"></span>
                                        Processing Receipt...
                                    </span>
                                    <span v-else>
                                        <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24" class="me-2">
                                            <path d="M13 10V3L4 14h7v7l9-11h-7z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                                        </svg>
                                        Extract Data
                                    </span>
                                </button>
                            </div>

                            <!-- Messages -->
                            <div v-if="successMessage" class="alert alert-success border-0 shadow-sm mt-3 d-flex align-items-center" role="alert">
                                <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24" class="me-2">
                                    <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                                </svg>
                                {{ successMessage }}
                            </div>

                            <div v-if="form.errors.image" class="alert alert-danger border-0 shadow-sm mt-3 d-flex align-items-center" role="alert">
                                <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24" class="me-2">
                                    <path d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                                </svg>
                                {{ form.errors.image }}
                            </div>

                            <div v-if="errors.error" class="alert alert-danger border-0 shadow-sm mt-3 d-flex align-items-center" role="alert">
                                <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24" class="me-2">
                                    <path d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                                </svg>
                                {{ errors.error }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Results Section -->
                <div class="col-lg-6">
                    <div class="card border-0 shadow-lg h-100" style="border-radius: 20px;">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center mb-4">
                                <div class="bg-success bg-opacity-10 rounded-circle p-2 me-3">
                                    <svg width="24" height="24" fill="currentColor" viewBox="0 0 24 24" class="text-success">
                                        <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                                    </svg>
                                </div>
                                <h2 class="h5 mb-0 fw-bold">Extracted Data</h2>
                            </div>

                            <!-- Debug Info (remove in production) -->
                            <div class="alert alert-info small mt-3" style="font-family: monospace;">
                                <strong>Debug Info:</strong><br>
                                receiptId: {{ receiptId }}<br>
                                isLoadingReceipt: {{ isLoadingReceipt }}<br>
                                result exists: {{ !!result }}<br>
                                result.id: {{ result?.id }}<br>
                                result.invoice_number: {{ result?.invoice_number }}
                            </div>

                            <!-- Loading State -->
                            <div v-if="isLoadingReceipt" class="text-center py-5">
                                <div class="mb-4">
                                    <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                </div>
                                <h5 class="text-muted fw-normal">Loading receipt data...</h5>
                                <p class="text-muted small mb-0">Please wait while we fetch your receipt details</p>
                            </div>

                            <!-- Empty State -->
                            <div v-else-if="!result" class="text-center py-5">
                                <div class="mb-4">
                                    <svg
                                        width="100"
                                        height="100"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                        style="color: #dee2e6;"
                                        class="mx-auto"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="1.5"
                                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                        />
                                    </svg>
                                </div>
                                <h5 class="text-muted fw-normal">No data yet</h5>
                                <p class="text-muted small mb-0">Upload and process a receipt to extract data</p>
                            </div>

                            <!-- Results -->
                            <div v-else>

                                <!-- Extracted Information -->
                                <div class="mb-4">
                                    <!-- Invoice Number -->
                                    <div class="mb-4 pb-4 border-bottom">
                                        <div class="d-flex align-items-start">
                                            <div class="bg-primary bg-opacity-10 rounded-3 p-3 me-3">
                                                <svg width="24" height="24" fill="currentColor" viewBox="0 0 24 24" class="text-primary">
                                                    <path d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                                                </svg>
                                            </div>
                                            <div class="flex-grow-1">
                                                <label class="text-muted small mb-1 d-block">Invoice Number</label>
                                                <div class="fs-4 fw-bold text-dark" v-if="result.invoice_number">
                                                    {{ result.invoice_number }}
                                                </div>
                                                <div class="text-muted fst-italic" v-else>
                                                    No invoice number found
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Supplier -->
                                    <div class="mb-4 pb-4 border-bottom">
                                        <div class="d-flex align-items-start">
                                            <div class="bg-success bg-opacity-10 rounded-3 p-3 me-3">
                                                <svg width="24" height="24" fill="currentColor" viewBox="0 0 24 24" class="text-success">
                                                    <path d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                                                </svg>
                                            </div>
                                            <div class="flex-grow-1">
                                                <label class="text-muted small mb-1 d-block">Supplier</label>
                                                <div class="fs-4 fw-bold text-dark" v-if="result.supplier">
                                                    {{ result.supplier }}
                                                </div>
                                                <div class="text-muted fst-italic" v-else>
                                                    No supplier found
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Date -->
                                    <div class="mb-4 pb-4 border-bottom">
                                        <div class="d-flex align-items-start">
                                            <div class="bg-info bg-opacity-10 rounded-3 p-3 me-3">
                                                <svg width="24" height="24" fill="currentColor" viewBox="0 0 24 24" class="text-info">
                                                    <path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                                                </svg>
                                            </div>
                                            <div class="flex-grow-1">
                                                <label class="text-muted small mb-1 d-block">Transaction Date</label>
                                                <div class="fs-4 fw-bold text-dark" v-if="result.transaction_date">
                                                    {{ new Date(result.transaction_date).toLocaleDateString('en-US', {
                                                        weekday: 'long',
                                                        year: 'numeric',
                                                        month: 'long',
                                                        day: 'numeric'
                                                    }) }}
                                                </div>
                                                <div class="text-muted fst-italic" v-else>
                                                    No date found
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Total Amount -->
                                    <div class="mb-4 pb-4 border-bottom">
                                        <div class="d-flex align-items-start">
                                            <div class="bg-warning bg-opacity-10 rounded-3 p-3 me-3">
                                                <svg width="24" height="24" fill="currentColor" viewBox="0 0 24 24" class="text-warning">
                                                    <path d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                                                </svg>
                                            </div>
                                            <div class="flex-grow-1">
                                                <label class="text-muted small mb-1 d-block">Total Amount</label>
                                                <div class="fs-3 fw-bold text-success" v-if="result.total_amount">
                                                    ${{ parseFloat(result.total_amount).toFixed(2) }}
                                                </div>
                                                <div class="text-muted fst-italic" v-else>
                                                    No total amount found
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Description -->
                                    <div class="mb-4 pb-4 border-bottom" v-if="result.description">
                                        <div class="d-flex align-items-start">
                                            <div class="bg-purple bg-opacity-10 rounded-3 p-3 me-3" style="--bs-bg-opacity: 0.1; background-color: #6f42c1 !important;">
                                                <svg width="24" height="24" fill="currentColor" viewBox="0 0 24 24" style="color: #6f42c1;">
                                                    <path d="M4 6h16M4 10h16M4 14h16M4 18h16" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                                                </svg>
                                            </div>
                                            <div class="flex-grow-1">
                                                <label class="text-muted small mb-1 d-block">Description / Items</label>
                                                <div class="text-dark">
                                                    {{ result.description }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Raw Extracted Text -->
                                    <div v-if="result.raw_text">
                                        <div class="d-flex align-items-start">
                                            <div class="bg-secondary bg-opacity-10 rounded-3 p-3 me-3">
                                                <svg width="24" height="24" fill="currentColor" viewBox="0 0 24 24" class="text-secondary">
                                                    <path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                                                </svg>
                                            </div>
                                            <div class="flex-grow-1">
                                                <label class="text-muted small mb-2 d-block">All Extracted Text from OCR</label>
                                                <div class="bg-light rounded-3 p-3" style="max-height: 300px; overflow-y: auto;">
                                                    <pre class="mb-0 small text-dark" style="white-space: pre-wrap; word-wrap: break-word; font-family: 'Courier New', monospace;">{{ result.raw_text }}</pre>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bottom Navigation -->
            <!-- <div class="text-center">
                <a href="/receipts" class="btn btn-light btn-lg px-4 shadow-sm">
                    <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24" class="me-2">
                        <path d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                    </svg>
                    View All Receipts
                </a>
            </div> -->
        </div>
    </div>
</template>

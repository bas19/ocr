<?php

namespace App\Http\Controllers;

use App\Contracts\OcrServiceInterface;
use App\Http\Requests\StoreReceiptRequest;
use App\Models\Receipt;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response;

class ReceiptController extends Controller
{
    public function __construct(public OcrServiceInterface $ocrService) {}

    /**
     * Display the upload page.
     */
    public function uploadPage(): InertiaResponse
    {
        // Get receipt ID from query string (more reliable than session flash)
        $receiptId = request()->query('receipt');

        // Also check session as fallback
        if (! $receiptId) {
            $receiptId = session('receipt_id');
        }

        return Inertia::render('Receipts/Upload', [
            'receiptId' => $receiptId,
        ]);
    }

    /**
     * Display a listing of receipts (UI page).
     */
    public function indexPage(): InertiaResponse
    {
        $receipts = Receipt::latest()->paginate(15);

        return Inertia::render('Receipts/Index', [
            'receipts' => $receipts,
        ]);
    }

    /**
     * Display a listing of receipts (API).
     */
    public function index(): JsonResponse
    {
        $receipts = Receipt::latest()->paginate(15);

        return response()->json($receipts);
    }

    /**
     * Store a newly uploaded receipt and process it.
     */
    public function store(StoreReceiptRequest $request): Response
    {
        try {
            // Store the uploaded image
            $imagePath = $request->file('image')->store('receipts', 'public');
            $fullPath = Storage::disk('public')->path($imagePath);

            // Extract text from the image
            $rawText = $this->ocrService->extractText($fullPath);

            // Parse receipt data
            $parsedData = $this->ocrService->parseReceiptData($rawText);

            // Save receipt to database
            $receipt = Receipt::create([
                'image_path' => $imagePath,
                'transaction_date' => $parsedData['transaction_date'],
                'invoice_number' => $parsedData['invoice_number'],
                'supplier' => $parsedData['supplier'],
                'total_amount' => $parsedData['total_amount'],
                'description' => $parsedData['description'],
                'raw_text' => $rawText,
                'metadata' => [
                    'processed_at' => now()->toIso8601String(),
                    'file_size' => $request->file('image')->getSize(),
                    'mime_type' => $request->file('image')->getMimeType(),
                ],
                'status' => 'processed',
            ]);

            // Redirect with receipt ID as query parameter (more reliable than session)
            return redirect()
                ->route('receipts.page.upload', ['receipt' => $receipt->id])
                ->with('message', 'Receipt processed successfully!');
        } catch (Exception $e) {
            Log::error('Receipt processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->withErrors([
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Display the specified receipt.
     */
    public function show(Receipt $receipt): JsonResponse
    {
        return response()->json($receipt);
    }

    /**
     * Remove the specified receipt from storage.
     */
    public function destroy(Receipt $receipt): JsonResponse
    {
        // Delete the image file
        if ($receipt->image_path && Storage::disk('public')->exists($receipt->image_path)) {
            Storage::disk('public')->delete($receipt->image_path);
        }

        $receipt->delete();

        return response()->json([
            'message' => 'Receipt deleted successfully',
        ]);
    }
}

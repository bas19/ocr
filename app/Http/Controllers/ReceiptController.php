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
        // If there's a receipt_id in the session, load the receipt
        $receipt = null;
        if ($receiptId = session('receipt_id')) {
            $receipt = Receipt::find($receiptId);
        }

        if ($receiptId = session('test')) {
            $receipt = [
            "id" => 25,
            "image_path" => "receipts/N3CpX2DNzk0ycZVPIbdJS75mtjzZy8Xm046Qesu4.png",
            "merchant_name" => null,
            "total_amount" => 93.5,
            "tax_amount" => null,
            "subtotal" => null,
            "transaction_date" => "2017-02-26T00:00:00.000000Z",
            "transaction_time" => null,
            "raw_text" => "Renco
    OF GPC ASIA PACIFIC PTY LTD
    993 283
    ER TO:
    ABN: 54 653 537 190
    I 2 YOU AUTOCARE
    URNACE ROAD
    TELEPHONE: 08 92971700
    FAX No.:
    7613158-0001
    STORE:RAP ELLENBROOK
    ADDRESS:T5/180 THE PROMENADE
    CHARGE TO:
    CAPRICORN SOCIETY LTD
    LOCKED BAG 3003
    -TAX INVOICE-
    ELLENBROOK
    CUSTOMER
    NUMBER
    P.O.No. WORKSHOP
    EXEMPT
    SHPOOL
    6106
    WEST PERTH
    TIME:
    01:44
    9999
    INVOICE
    SALES
    PERSON SHANNAN
    NO
    4680648477
    SALES
    REP
    AM058
    DATE
    17/02/26
    SOURCE TRADE COUNTE BRANCH No.
    CARRIER DELV
    468
    RELEASE No.
    XQ49871-000
    PART NUMBER
    DESCRIPTION
    RBRAK20L-V2
    CLEANER
    BRAKE CLEANER 20L
    RETAIL
    INCL GST
    125.00
    UOM
    QTY
    BACK QTY
    ORDERED ORDERED SUPPLIED
    EACH
    1
    1
    ** CAPRICORN 063888
    UNIT PRICE TOTAL GST
    EXCL GST
    85.00 3
    **
    S
    8.50
    TOTAL
    INCL GST
    93.50
    REPCO ELLENBROOK TRADING HOURS
    WEEKDAYS: 8:00AM - 5:00PM
    SATURDAY: 8:00AM - 5:00PM
    SUNDAY & PUB HOLS: 11:00AM 5:00PM
    GST
    PAYABLE
    S=GST CODE
    0
    FREE
    E. &O.E.
    *
    8.50
    TOTAL
    93.50
    MO
    3
    10 %
    TERMS AND CONDITIONS WILL ONLY BE PRINTED ON THE FIRST INVOICE OR CREDIT OF THE MONTH FOR EACH CUSTOMER ACCOUNT.
    (Refer to your \"Customer Trading Agreement\" or the first invoice/credit of each month for Terms and Conditions)
    1 OF",
        "items" => null,
        "metadata" => [
            "processed_at" => "2026-03-05T12:27:55+00:00",
            "file_size" => 3859180,
            "mime_type" => "image/png"
        ],
        "status" => "processed",
        "error_message" => null,
        "created_at" => "2026-03-05T12:27:55.000000Z",
        "updated_at" => "2026-03-05T12:27:55.000000Z",
        "invoice_number" => "4680648477",
        "supplier" => "Renco",
        "description" => "Renco | OF GPC ASIA PACIFIC PTY LTD | ABN: 54 653 537 190 | I 2 YOU AUTOCARE | URNACE ROAD"
            ];
        }

        return Inertia::render('Receipts/Upload', [
            'receipt' => $receipt,
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
        // return redirect()->route('receipts.page.upload')->with([
        //     'test' => true,
        //     'message' => 'Receipt processed successfully!',
        // ]);

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

            // Redirect with success message and receipt ID (not the full data)
            return redirect()->route('receipts.page.upload')->with([
                'receipt_id' => $receipt->id,
                'message' => 'Receipt processed successfully!',
            ]);
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

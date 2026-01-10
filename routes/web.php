<?php


Route::fallback(function () {
    return response()->json([
        'success' => false,
        'message' => 'Endpoint tidak ditemukan. Silakan periksa dokumentasi API kami.',
    ], 404);
});

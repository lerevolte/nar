<?php

namespace App\Http\Controllers;

use App\Models\PromoCode;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PromoCodeController extends Controller
{
    private const ADMIN_IDS = [288559694, 154483653];

    private function isAdmin(Request $request): bool
    {
        $user = $request->get('auth_user');
        return $user && in_array($user->user_id, self::ADMIN_IDS);
    }

    /**
     * Страница промокодов
     */
    public function index(Request $request)
    {
        if (!$this->isAdmin($request)) abort(403);

        $promoCodes = PromoCode::where('max_uses', '>', 1)->orderByDesc('created_at')->paginate(30);

        $stats = [
            'total' => PromoCode::where('max_uses', '>', 1)->count(),
            'active' => PromoCode::where('max_uses', '>', 1)->where('is_active', 1)->count(),
            'total_uses' => PromoCode::where('max_uses', '>', 1)->sum('current_uses'),
        ];

        return view('admin.promo.index', compact('promoCodes', 'stats'));
    }

    /**
     * API: создать промокод(ы)
     */
    public function create(Request $request)
    {
        if (!$this->isAdmin($request)) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $request->validate([
            'code' => 'nullable|string|max:100',
            'type' => 'required|string|in:song',
            'songs_amount' => 'required|integer|min:1|max:999',
            'value' => 'required|integer|min:0',
            'max_uses' => 'required|integer|min:1|max:100000',
            'quantity' => 'required|integer|min:1|max:500',
        ]);

        $quantity = $request->input('quantity');
        $customCode = $request->input('code');
        $codes = [];

        // Если указан конкретный код и quantity=1
        if ($customCode && $quantity === 1) {
            $customCode = strtoupper(trim($customCode));

            if (PromoCode::where('code', $customCode)->exists()) {
                return response()->json(['error' => "Код {$customCode} уже существует"], 400);
            }

            PromoCode::create([
                'code' => $customCode,
                'type' => $request->input('type'),
                'value' => $request->input('value'),
                'songs_amount' => $request->input('songs_amount'),
                'songs_count' => $request->input('songs_amount'),
                'max_uses' => $request->input('max_uses'),
                'current_uses' => 0,
                'is_active' => 1,
            ]);
            $codes[] = $customCode;
        } else {
            // Массовая генерация
            $prefix = $customCode ? strtoupper(trim($customCode)) : '';

            for ($i = 0; $i < $quantity; $i++) {
                $code = $prefix
                    ? $prefix . '-' . strtoupper(Str::random(5))
                    : strtoupper(Str::random(8));

                // Уникальность
                $attempts = 0;
                while (PromoCode::where('code', $code)->exists() && $attempts < 10) {
                    $code = $prefix
                        ? $prefix . '-' . strtoupper(Str::random(5))
                        : strtoupper(Str::random(8));
                    $attempts++;
                }

                PromoCode::create([
                    'code' => $code,
                    'type' => $request->input('type'),
                    'value' => $request->input('value'),
                    'songs_amount' => $request->input('songs_amount'),
                    'songs_count' => $request->input('songs_amount'),
                    'max_uses' => $request->input('max_uses'),
                    'current_uses' => 0,
                    'is_active' => 1,
                ]);
                $codes[] = $code;
            }
        }

        return response()->json([
            'success' => true,
            'codes' => $codes,
            'count' => count($codes),
        ]);
    }

    /**
     * API: вкл/выкл промокод
     */
    public function toggle(Request $request)
    {
        if (!$this->isAdmin($request)) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $request->validate([
            'id' => 'required|integer',
            'is_active' => 'required|boolean',
        ]);

        $promo = PromoCode::findOrFail($request->input('id'));
        $promo->update(['is_active' => $request->input('is_active')]);

        return response()->json([
            'success' => true,
            'is_active' => $promo->is_active,
        ]);
    }
}
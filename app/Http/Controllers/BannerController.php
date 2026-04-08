<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use App\Http\Requests\StoreBannerRequest;
use App\Http\Requests\UpdateBannerRequest;
use App\Http\Resources\BannerResource;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BannerController extends Controller
{
    public function index()
    {
        $banners = Banner::orderBy('sort_order', 'asc')->paginate(15);
        return BannerResource::collection($banners)
            ->additional(['has_more' => $banners->hasMorePages()]);
    }

    public function indexPublic()
    {
        $banners = Banner::where('is_active', true)
            ->orderBy('sort_order', 'asc')
            ->get();

        return BannerResource::collection($banners);
    }

    public function store(StoreBannerRequest $request)
    {
        $data = $this->sanitizePayload($request->validated());
        $path = null;

        try {
            DB::beginTransaction();

            if ($request->hasFile('image')) {
                $path = $request->file('image')->store('banners', 'public');
                $data['image'] = $path;
            }

            $banner = Banner::create($data);

            DB::commit();

            return new BannerResource($banner);
        } catch (\Exception $e) {
            DB::rollBack();
            if ($path) {
                Storage::disk('public')->delete($path);
            }
            Log::error('Banner store failed: ' . $e->getMessage());
            return response()->json(['message' => 'حدث خطأ أثناء الحفظ'], 500);
        }
    }

    public function show(Banner $banner)
    {
        return new BannerResource($banner);
    }

    public function update(UpdateBannerRequest $request, Banner $banner)
    {
        $data = $this->sanitizePayload($request->validated());
        $oldImage = $banner->image;
        $newPath = null;

        try {
            DB::beginTransaction();

            if ($request->hasFile('image')) {
                $newPath = $request->file('image')->store('banners', 'public');
                $data['image'] = $newPath;
            }

            $banner->update($data);

            if ($request->hasFile('image') && $oldImage) {
                Storage::disk('public')->delete($oldImage);
            }

            DB::commit();

            return new BannerResource($banner);
        } catch (\Exception $e) {
            DB::rollBack();
            if ($newPath) {
                Storage::disk('public')->delete($newPath); // Revert the physical upload if DB fails
            }
            Log::error('Banner update failed: ' . $e->getMessage());
            return response()->json(['message' => 'حدث خطأ أثناء التعديل'], 500);
        }
    }

    public function destroy(Banner $banner)
    {
        if ($banner->image) {
            Storage::disk('public')->delete($banner->image);
        }
        $banner->delete();
        return response()->json(['message' => 'Deleted successfully']);
    }

    public function toggle(Banner $banner)
    {
        $banner->update(['is_active' => !$banner->is_active]);
        return response()->json(['message' => 'Toggled successfully', 'is_active' => $banner->is_active]);
    }

    /**
     * نظف البيانات بحسب النوع لتجنب تضارب الـ IDs
     */
    private function sanitizePayload($data)
    {
        if (isset($data['type'])) {
            $type = $data['type'];
            if ($type === 'link') {
                $data['category_id'] = null;
                $data['product_id'] = null;
            } elseif ($type === 'category') {
                $data['url'] = null;
                $data['product_id'] = null;
            } elseif ($type === 'product') {
                $data['url'] = null;
                $data['category_id'] = null;
            } elseif ($type === 'none') {
                $data['url'] = null;
                $data['category_id'] = null;
                $data['product_id'] = null;
            }
        }
        return $data;
    }
}

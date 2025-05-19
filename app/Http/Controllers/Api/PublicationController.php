<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Alumni;
use App\Models\Publication;
use Illuminate\Http\Request;
use Str;

class PublicationController extends Controller
{
    // 1. Get all publikasi
    public function index()
    {
        return response()->json(Publication::all());
    }

    // 1. Get publikasi by slug
    public function show($slug)
    {
        $publikasi = Publication::where('slug', $slug)->first();

        if (!$publikasi) {
            return response()->json(['message' => 'Data not found'], 404);
        }

        return response()->json($publikasi);
    }

    // 2. Create new publikasi with auto slug
    public function store(Request $request)
    {
        $validated = $request->validate([
            'pub_full_name' => 'required|string|max:255',
            'pub_short_name' => 'nullable|string|max:150',
            'description' => 'nullable|text',
            'subject' => 'nullable|string|max:150',
            'doc_type' => 'nullable|string|max:100',
            'pub_file' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx|max:50240', // max 10MB
            'pub_file_type' => 'nullable|string|max:50',
            'language' => 'nullable|string|max:50',
            'source' => 'nullable|string|max:255',
        ]);

        // Generate unique slug
        $slug = \Illuminate\Support\Str::slug($validated['pub_full_name']);
        $count = 1;
        while (Publication::where('slug', $slug)->exists()) {
            $slug = \Illuminate\Support\Str::slug($validated['pub_full_name']) . '-' . $count++;
        }

        $validated['slug'] = $slug;

        // Handle file upload
        if ($request->hasFile('pub_file')) {
            $file = $request->file('pub_file');
            $path = $file->store('publications', 'public'); // stored in storage/app/public/publications
            $validated['pub_file'] = 'storage/' . $path;
            $validated['pub_file_type'] = $file->getClientOriginalExtension(); // pdf, docx, etc
        }

        $publikasi = Publication::create($validated);

        return response()->json($publikasi, 201);
    }


    // 3. Update publikasi by id
    public function update(Request $request, $id)
    {
        $publikasi = Publication::find($id);
        if (!$publikasi) {
            return response()->json(['message' => 'Data not found'], 404);
        }

        $validated = $request->validate([
            'pub_full_name' => 'sometimes|required|string|max:255',
            'pub_short_name' => 'nullable|string|max:150',
            'description' => 'nullable|string',
            'subject' => 'nullable|string|max:150',
            'doc_type' => 'nullable|string|max:100',
            'pub_file' => 'nullable|string|max:255',
            'pub_file_type' => 'nullable|string|max:50',
            'language' => 'nullable|string|max:50',
            'source' => 'nullable|string|max:255',
        ]);

        // Update slug if pub_full_name changes
        if (isset($validated['pub_full_name']) && $validated['pub_full_name'] !== $publikasi->pub_full_name) {
            $slug = \Illuminate\Support\Str::slug($validated['pub_full_name']);
            $count = 1;
            while (Publication::where('slug', $slug)->where('id', '!=', $id)->exists()) {
                $slug = \Illuminate\Support\Str::slug($validated['pub_full_name']) . '-' . $count++;
            }
            $validated['slug'] = $slug;
        }

        $publikasi->update($validated);

        return response()->json($publikasi);
    }

    // 4. Update access_count by id (increment or set)
    public function updateAccessCount(Request $request, $id)
    {
        $publikasi = Publication::find($id);
        if (!$publikasi) {
            return response()->json(['message' => 'Data not found'], 404);
        }

        $accessCount = $request->input('access_count');
        if (is_null($accessCount)) {
            return response()->json(['message' => 'access_count is required'], 400);
        }

        $publikasi->access_count = $accessCount;
        $publikasi->save();

        return response()->json(['message' => 'access_count updated', 'access_count' => $publikasi->access_count]);
    }

    // 5. Update tanggal_count by id (increment or set)
    public function updateTanggalCount(Request $request, $id)
    {
        $publikasi = Publication::find($id);
        if (!$publikasi) {
            return response()->json(['message' => 'Data not found'], 404);
        }

        $tanggalCount = $request->input('tanggal_count');
        if (is_null($tanggalCount)) {
            return response()->json(['message' => 'tanggal_count is required'], 400);
        }

        $publikasi->tanggal_count = $tanggalCount;
        $publikasi->save();

        return response()->json(['message' => 'tanggal_count updated', 'tanggal_count' => $publikasi->tanggal_count]);
    }
}

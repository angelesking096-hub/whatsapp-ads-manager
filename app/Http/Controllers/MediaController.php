<?php
namespace App\Http\Controllers;
use App\Models\MediaAsset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
class MediaController extends Controller
{
    private function tenant(Request $request) { $tenant = $request->user()->currentTenant(); abort_unless($tenant, 403); return $tenant; }
    public function index(Request $request)
    {
        $tenant = $this->tenant($request);
        $assets = MediaAsset::where('tenant_id',$tenant->id)->latest()->paginate(20);
        return view('dashboard.media', compact('tenant','assets'));
    }
    public function store(Request $request)
    {
        $tenant = $this->tenant($request);
        $data = $request->validate(['media'=>['required','file','mimes:jpg,jpeg,png,webp,gif,mp4,mov,pdf','max:20480']]);
        $file = $data['media'];
        $path = $file->store('tenants/'.$tenant->id.'/media', 'local');
        MediaAsset::create(['tenant_id'=>$tenant->id,'user_id'=>$request->user()->id,'original_name'=>mb_substr($file->getClientOriginalName(),0,255),'path'=>$path,'mime_type'=>$file->getMimeType(),'size_bytes'=>$file->getSize()]);
        return redirect()->route('media.index')->with('success','Fichier ajouté à la bibliothèque privée.');
    }
    public function download(Request $request, MediaAsset $mediaAsset)
    {
        abort_unless($mediaAsset->tenant_id === $this->tenant($request)->id, 404);
        abort_unless(Storage::disk('local')->exists($mediaAsset->path), 404);
        return Storage::disk('local')->download($mediaAsset->path, $mediaAsset->original_name);
    }
    public function destroy(Request $request, MediaAsset $mediaAsset)
    {
        abort_unless($mediaAsset->tenant_id === $this->tenant($request)->id, 404);
        Storage::disk('local')->delete($mediaAsset->path); $mediaAsset->delete();
        return redirect()->route('media.index')->with('success','Fichier supprimé.');
    }
}

import React, { useCallback } from 'react';
import { Label } from '@/Components/ui/label';
import { Input } from '@/Components/ui/input';
import { Image as ImageIcon, Video, X, UploadCloud } from 'lucide-react';
import { useDropzone } from 'react-dropzone';

export default function GalleryStep({ data, setData, errors }: any) {
    const onDrop = useCallback((acceptedFiles: File[]) => {
        if (data.gallery.length + acceptedFiles.length <= 5) {
            setData('gallery', [...data.gallery, ...acceptedFiles]);
        }
    }, [data.gallery, setData]);

    const { getRootProps, getInputProps, isDragActive } = useDropzone({
        onDrop,
        accept: { 'image/*': ['.jpeg', '.png', '.jpg', '.webp'] },
        maxSize: 5 * 1024 * 1024, // 5MB
        disabled: data.gallery.length >= 5
    });

    const removeImage = (idx: number) => {
        setData('gallery', data.gallery.filter((_: any, i: number) => i !== idx));
    };

    return (
        <div className="space-y-10">
            <div>
                <h2 className="text-2xl font-bold text-slate-900 mb-1">Showcase Your Services</h2>
                <p className="text-sm text-slate-500">Encourage buyers to choose your service by featuring a variety of your work.</p>
            </div>

            <div className="space-y-4">
                <div>
                    <h3 className="text-lg font-bold text-slate-900 flex items-center gap-2 mb-1">
                        <ImageIcon className="w-5 h-5 text-indigo-500" /> Images (up to 5)
                    </h3>
                    <p className="text-sm text-slate-500 mb-4">Get noticed by the right buyers with visual examples of your services.</p>
                </div>

                <div className="grid grid-cols-2 md:grid-cols-3 gap-4">
                    {/* Existing Images */}
                    {data.gallery.map((file: File, idx: number) => (
                        <div key={idx} className="relative aspect-[4/3] rounded-2xl overflow-hidden border border-slate-200 group bg-slate-100">
                            <img src={URL.createObjectURL(file)} alt={`Upload ${idx}`} className="w-full h-full object-cover" />
                            <div className="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                <button type="button" onClick={() => removeImage(idx)} className="p-2 bg-white rounded-full text-red-500 hover:scale-110 transition-transform">
                                    <X className="w-5 h-5" />
                                </button>
                            </div>
                            {idx === 0 && (
                                <div className="absolute top-2 left-2 bg-emerald-500 text-white text-[10px] font-bold px-2 py-1 rounded shadow-sm">
                                    PRIMARY
                                </div>
                            )}
                        </div>
                    ))}

                    {/* Upload Dropzone */}
                    {data.gallery.length < 5 && (
                        <div 
                            {...getRootProps()} 
                            className={`aspect-[4/3] rounded-2xl border-2 border-dashed flex flex-col items-center justify-center p-6 text-center cursor-pointer transition-all ${
                                isDragActive ? 'border-indigo-500 bg-indigo-50/50' : 'border-slate-300 bg-slate-50 hover:bg-slate-100'
                            }`}
                        >
                            <input {...getInputProps()} />
                            <UploadCloud className={`w-8 h-8 mb-3 ${isDragActive ? 'text-indigo-500' : 'text-slate-400'}`} />
                            <p className="text-sm font-bold text-slate-700">Drag & drop photos or</p>
                            <p className="text-sm font-bold text-indigo-600 mb-1">Browse</p>
                            <p className="text-[10px] text-slate-400 uppercase tracking-wider font-bold">Max 5MB</p>
                        </div>
                    )}
                </div>
                {errors.gallery && <p className="text-xs text-red-500 font-medium">{errors.gallery}</p>}
                {(errors as any)['gallery.0'] && <p className="text-xs text-red-500 font-medium">Please upload at least one image.</p>}
            </div>

            <div className="border-t border-slate-200 pt-10 space-y-4">
                <div>
                    <h3 className="text-lg font-bold text-slate-900 flex items-center gap-2 mb-1">
                        <Video className="w-5 h-5 text-indigo-500" /> Video (Optional)
                    </h3>
                    <p className="text-sm text-slate-500">Capture buyers' attention with a video that showcases your service.</p>
                </div>

                <div className="space-y-2 max-w-xl">
                    <Label className="text-xs font-bold text-slate-600 uppercase tracking-wider">YouTube or Vimeo URL</Label>
                    <Input
                        value={data.video_url || ''}
                        onChange={e => setData('video_url', e.target.value)}
                        placeholder="https://youtube.com/watch?v=..."
                        className="h-12"
                    />
                    {errors.video_url && <p className="text-xs text-red-500 font-medium">{errors.video_url}</p>}
                </div>
            </div>
        </div>
    );
}

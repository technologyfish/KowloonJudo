<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UploadController extends Controller
{
    /**
     * 上传头像（小程序端）
     * POST /api/upload/avatar
     */
    public function avatar(Request $request)
    {
        return $this->handleUpload($request, 'avatars');
    }

    /**
     * 通用图片上传（管理端）
     * POST /api/admin/upload
     */
    public function image(Request $request)
    {
        return $this->handleUpload($request, 'images', 10 * 1024 * 1024); // 管理端允许 10MB
    }

    /**
     * 统一上传处理 & 压缩
     */
    private function handleUpload(Request $request, string $folder = 'images', int $maxSize = 5 * 1024 * 1024)
    {
        if (!$request->hasFile('file')) {
            return $this->error('请选择要上传的文件', 422);
        }

        $file = $request->file('file');

        // 校验 MIME（兼容未开启 fileinfo 扩展的环境）
        $mime = null;
        try {
            $mime = $file->getMimeType();
        } catch (\Throwable $e) {
            // fileinfo 扩展未启用，通过文件扩展名推断
        }

        if (!$mime) {
            $extMimeMap = [
                'jpg'  => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'png'  => 'image/png',
                'gif'  => 'image/gif',
                'webp' => 'image/webp',
            ];
            $ext  = strtolower($file->getClientOriginalExtension() ?: '');
            $mime = $extMimeMap[$ext] ?? 'application/octet-stream';
        }

        $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($mime, $allowedMimes)) {
            return $this->error('仅支持 jpg/png/gif/webp 格式', 422);
        }

        // 校验大小
        if ($file->getSize() > $maxSize) {
            $maxMB = intval($maxSize / 1024 / 1024);
            return $this->error("文件大小不能超过 {$maxMB}MB", 422);
        }

        // 保存目录
        $dir = base_path("public/uploads/{$folder}");
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $filename = date('Ymd') . '_' . uniqid() . '.jpg'; // 统一输出 jpg
        $savePath = $dir . DIRECTORY_SEPARATOR . $filename;

        // 压缩 & 转为 JPEG（GD 库）
        if ($this->compressImage($file->getRealPath(), $savePath, $mime)) {
            // 压缩成功
        } else {
            // GD 失败时回退到原文件
            $ext      = $file->getClientOriginalExtension() ?: 'jpg';
            $filename = date('Ymd') . '_' . uniqid() . '.' . $ext;
            $file->move($dir, $filename);
        }

        // 拼接可访问 URL
        $baseUrl = rtrim(env('APP_URL', 'http://localhost:8000'), '/');
        $url     = $baseUrl . "/uploads/{$folder}/" . $filename;

        return $this->success(['url' => $url], '上传成功');
    }

    /**
     * 使用 GD 库压缩图片
     * - 最大宽度 1920px（等比缩放）
     * - JPEG 质量 80
     */
    private function compressImage(string $srcPath, string $destPath, string $mime, int $maxWidth = 1920, int $quality = 80): bool
    {
        try {
            switch ($mime) {
                case 'image/jpeg':
                    $img = imagecreatefromjpeg($srcPath);
                    break;
                case 'image/png':
                    $img = imagecreatefrompng($srcPath);
                    break;
                case 'image/gif':
                    $img = imagecreatefromgif($srcPath);
                    break;
                case 'image/webp':
                    $img = imagecreatefromwebp($srcPath);
                    break;
                default:
                    return false;
            }

            if (!$img) {
                return false;
            }

            $origW = imagesx($img);
            $origH = imagesy($img);

            // 等比缩放
            if ($origW > $maxWidth) {
                $newW = $maxWidth;
                $newH = intval($origH * ($maxWidth / $origW));
                $resized = imagecreatetruecolor($newW, $newH);
                // 保留透明背景（PNG / GIF → 白底）
                $white = imagecolorallocate($resized, 255, 255, 255);
                imagefill($resized, 0, 0, $white);
                imagecopyresampled($resized, $img, 0, 0, 0, 0, $newW, $newH, $origW, $origH);
                imagedestroy($img);
                $img = $resized;
            }

            imagejpeg($img, $destPath, $quality);
            imagedestroy($img);

            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }
}

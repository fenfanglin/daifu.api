<?php

namespace app\business\controller;

use app\extend\common\Common;

use TencentCloud\Common\Credential;
use TencentCloud\Common\Profile\ClientProfile;
use TencentCloud\Common\Profile\HttpProfile;
use TencentCloud\Common\Exception\TencentCloudSDKException;
use TencentCloud\Ocr\V20181119\OcrClient;
use TencentCloud\Ocr\V20181119\Models\QrcodeOCRRequest;

class CommonController extends AuthController
{
    public function upload()
    {
        $file = request()->file('file');
        $extension = strtolower($file->getOriginalExtension());

        if (in_array($extension, ['php', 'sh'])) {
            return $this->returnError('可执行文件禁止上传到本地服务器');
        }
        $name = md5_file($file) . '.' . $extension;
        $date = date('Ymd');
        $folder = '../public/uploads/' . $date.'/';
        $real_folder = '/uploads/' . $date.'/';
        $file->move($folder, $name);
        return $this->returnData(['fileName' => $real_folder . $name]);
    }
}

<?php
namespace app\business\controller;

use app\extend\common\Common;

use TencentCloud\Common\Credential;
use TencentCloud\Common\Profile\ClientProfile;
use TencentCloud\Common\Profile\HttpProfile;
use TencentCloud\Common\Exception\TencentCloudSDKException;
use TencentCloud\Ocr\V20181119\OcrClient;
use TencentCloud\Ocr\V20181119\Models\QrcodeOCRRequest;

class UploadController extends AuthController
{
	public function qr_decode()
	{
		$secret_id = 'AKIDl5Vlx260FJDxkOOO8ghbLRL6iEYlDED4';
		$secret_key = '2S8ynqpeN5rVi1IyasWH7g7PYcWm5OL6';
		$region = 'ap-guangzhou';
		try {
			// 实例化一个认证对象，入参需要传入腾讯云账户 SecretId 和 SecretKey，此处还需注意密钥对的保密
			// 代码泄露可能会导致 SecretId 和 SecretKey 泄露，并威胁账号下所有资源的安全性。以下代码示例仅供参考，建议采用更安全的方式来使用密钥，请参见：https://cloud.tencent.com/document/product/1278/85305
			// 密钥可前往官网控制台 https://console.cloud.tencent.com/cam/capi 进行获取
			$cred = new Credential($secret_id, $secret_key);
			// 实例化一个http选项，可选的，没有特殊需求可以跳过
			$httpProfile = new HttpProfile();
			$httpProfile->setEndpoint("ocr.tencentcloudapi.com");
			
			// 实例化一个client选项，可选的，没有特殊需求可以跳过
			$clientProfile = new ClientProfile();
			$clientProfile->setHttpProfile($httpProfile);
			// 实例化要请求产品的client对象,clientProfile是可选的
			$client = new OcrClient($cred, $region, $clientProfile);

			// 实例化一个请求对象,每个接口都会对应一个request对象
			$req = new QrcodeOCRRequest();
			// $image = file_get_contents('static/error.jpg');
			
			$file = request()->file('file');
			$img = file_get_contents($file->getPathname());
			
			$base64 = base64_encode($img);
			$params = array(
				'ImageBase64' => $base64
			);
			$req->fromJsonString(json_encode($params));
			
			Common::writeLog([
				'file_name' => $file->getOriginalName(),
				'file_size' => $file->getSize(),
				'file_path' => $file->getPathname(),
			], 'upload_qr_decode');
			
			// 返回的resp是一个QrcodeOCRResponse的实例，与请求对象对应
			$resp = $client->QrcodeOCR($req);
			
			// 输出json格式的字符串回包
			$result = json_decode($resp->toJsonString(),true);
			Common::writeLog(['result' => $result], 'upload_qr_decode');
			
			if (isset($result['CodeResults'][0]['TypeName']) && $result['CodeResults'][0]['TypeName'] == 'QR_CODE'){
				echo $result['CodeResults'][0]['Url'];
			}else{
				echo '';
			}
		}
		catch(TencentCloudSDKException $e) {
			Common::writeLog(['error' => $e->getMessage()], 'upload_qr_decode_error');
			
			echo $e->getMessage();
		}
	}
}
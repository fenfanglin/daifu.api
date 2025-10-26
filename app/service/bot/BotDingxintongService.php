<?php
namespace app\service\bot;

use app\extend\common\Common;
use Spatie\PdfToImage\Pdf;

class BotDingxintongService
{

	//消息数据
	public $token;

	public $message_id;

	public $order_info;

	public function __construct($token, $message_id, $order_info)
	{
		$this->token = $token;
		$this->message_id = $message_id;
		$this->order_info = $order_info;
	}

	public function get_pdf($out_trade_no, $chatId, $text)
	{
		$config = [
			'mchid' => $this->order_info->cardBusiness->channelAccount->mchid ?? '',
			'appid' => $this->order_info->cardBusiness->channelAccount->appid ?? '',
			'key_id' => $this->order_info->cardBusiness->channelAccount->key_id ?? '',
			'key_secret' => $this->order_info->cardBusiness->channelAccount->key_secret ?? '',
		];
		$service = new \app\service\api\DingxintongService($config);
		$info = json_decode($this->order_info->info, true);
		$res = $service->bill_url($info['order_id'], $this->order_info->out_trade_no);
		if (!isset($res['data']['data']))
		{
			return ['ok' => false, 'error' => '❌ 获取pdf下载地址失败！'];
		}

		$ch = curl_init();
		curl_setopt_array($ch, [
			CURLOPT_URL => $res['data']['data'],
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_SSL_VERIFYPEER => false
		]);
		$pdfContent = curl_exec($ch);

		$tempDir = root_path() . 'public/df_pdf/';
		if (!file_exists($tempDir))
		{
			mkdir($tempDir, 0777, true);
		}
		$pdf_path = $tempDir . $out_trade_no . '.pdf';
		if (!file_exists($pdf_path))
		{
			file_put_contents($pdf_path, $pdfContent);
		}
		$this->convertPdfToImageAndSend($pdf_path, $chatId, $text);
	}

	/**
	 * 🔥 核心方法：PDF转图片 + 发送Telegram（无exec）
	 */
	public function convertPdfToImageAndSend($pdfPath, $chat_id, $caption)
	{
		// Step 1: 转换为图片
		$imagePaths = self::convertPdfToImages($pdfPath);
		if (!$imagePaths)
		{
			return ['ok' => false, 'error' => '❌ PDF转图片失败'];
		}
		// Step 2: 发送每张图片到Telegram
		$successCount = 0;

		foreach ($imagePaths as $imagePath)
		{
			$sendResult = self::sendImageToTelegram($this->token, $chat_id, $imagePath, $caption);
			if ($sendResult['ok'])
			{
				$successCount++;
			}
			@unlink($imagePath); // 清理临时文件
		}

		return [
			'ok' => $successCount > 0,
			'error' => $successCount === 0 ? '❌ 所有图片发送失败' : "✅ 发送了 {$successCount} 张图片",
			'sent_count' => $successCount
		];
	}

	/**
	 * PDF转图片（使用 spatie/pdf-to-image）
	 */
	public static function convertPdfToImages($pdfPath)
	{
		$imagePaths = [];
		$tempDir = root_path() . 'temp_images/';
		if (!file_exists($tempDir))
		{
			mkdir($tempDir, 0777, true);
		}

		try
		{
			$pdf = new Pdf($pdfPath);
			$pageCount = $pdf->getNumberOfPages();

			for ($page = 1; $page <= $pageCount; $page++)
			{
				$imagePath = $tempDir . 'page_' . $page . '.png';
				$pdf->setPage($page)
					->setResolution(300) // 高清
					->saveImage($imagePath);

				if (file_exists($imagePath))
				{
					$imagePaths[] = $imagePath;
				}
			}
		}
		catch (\Exception $e)
		{
			return false;
		}

		return !empty($imagePaths) ? $imagePaths : false;
	}

	/**
	 * 发送图片到Telegram
	 */
	public function sendImageToTelegram($botToken, $chatId, $imagePath, $caption)
	{
		if (!file_exists($imagePath))
		{
			return ['ok' => false, 'error' => '❌ 图片文件不存在'];
		}

		$url = "https://api.telegram.org/bot{$botToken}/sendPhoto";
		$postData = [
			'chat_id' => $chatId,
			'caption' => $caption,
			'photo' => new \CURLFile($imagePath, 'image/png', basename($imagePath)),
			'reply_to_message_id' => $this->message_id
		];

		$ch = curl_init();
		curl_setopt_array($ch, [
			CURLOPT_URL => $url,
			CURLOPT_POST => true,
			CURLOPT_POSTFIELDS => $postData,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_TIMEOUT => 60
		]);

		$response = curl_exec($ch);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		$result = json_decode($response, true);
		return $result['ok']
			? ['ok' => true, 'message_id' => $result['result']['message_id']]
			: ['ok' => false, 'error' => $result['description']];
	}
}

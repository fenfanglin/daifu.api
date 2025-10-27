<?php
namespace app\admin\controller;

use app\extend\common\Common;
use app\extend\common\BaseController;

class Demo1Controller extends BaseController
{
	public function query()
	{
		$config = [
			'mchid' => 'M1759755553',
			'appid' => '68e3bd22e4b02b5ff8bc1d63',
			'key' => 'ppp42ij2dzzbd5kx1bevv0m15mx8lvtchwqvoweu5u55qonrgvf8sp4d8q448k5ve8bxm2bhej6dmk8jffazipkhj62zaftac81ted7lfg5zt36gjzyrvzz6kunzac46',
		];

		$service = new \app\service\api\ShundatongService($config);

		// $trans_id = 'T1975220023580078082';
		// $order_no = 'M17597640259103567';
		$trans_id = 'T1975532107903709185';
		$order_no = 'DM2025100720021200295';

		$res = $service->query($trans_id, $order_no);

		dd($res);
	}

	public function create()
	{
		$config = [
			'mchid' => 'M1759755553',
			'appid' => '68e3bd22e4b02b5ff8bc1d63',
			'key' => 'ppp42ij2dzzbd5kx1bevv0m15mx8lvtchwqvoweu5u55qonrgvf8sp4d8q448k5ve8bxm2bhej6dmk8jffazipkhj62zaftac81ted7lfg5zt36gjzyrvzz6kunzac46',
		];

		$service = new \app\service\api\ShundatongService($config);

		$data = [
			'out_trade_no' => 'DM' . date('YmdHis') . '00' . mt_rand(100, 999),
			'amount' => '10',

			'account_type' => 1,
			'account' => '6216710470059167678',
			'account_name' => '万军杰',
			'bank' => '中国农业银行',

			// 'account_type' => 3,
			// 'account' => '15736338534',
			// 'account_name' => '万军杰',
		];

		$res = $service->create($data);

		dd($res);
	}

	public function query2()
	{
		$config = [
			'mchid' => '1977392152004120577',
			'appid' => '2021004124686129',
			'key_id' => 'IJ0CDFUTHRT38OGYXDESNLBJ',
			'key_secret' => 'HBMW3SLAHI8R5DZXP6HYW60NWWOJUY',
		];

		$service = new \app\service\api\DingxintongService($config);

		$order_no = 'UD202510131821274854';

		$res = $service->query($order_no);

		dd($res);
	}

	public function account_info2()
	{
		$config = [
			'mchid' => '1977392152004120577',
			'appid' => '2021004124686129',
			'key_id' => 'IJ0CDFUTHRT38OGYXDESNLBJ',
			'key_secret' => 'HBMW3SLAHI8R5DZXP6HYW60NWWOJUY',
		];

		$service = new \app\service\api\DingxintongService($config);

		$res = $service->account_info();

		dd($res);
	}

	public function bill_url1()
	{
		$config = [
			'mchid' => '1977392152004120577',
			'appid' => '2021004124686129',
			'key_id' => 'IJ0CDFUTHRT38OGYXDESNLBJ',
			'key_secret' => 'HBMW3SLAHI8R5DZXP6HYW60NWWOJUY',
		];

		$service = new \app\service\api\DingxintongService($config);

		$order_id = '1978497723880468482';
		$order_no = '202510160746528524';

		$res = $service->bill_url($order_id, $order_no);

		dd($res);
	}

	public function bill_url2()
	{
		$ip = $_SERVER['REMOTE_ADDR']; // 默认使用 REMOTE_ADDR (可能是 8.219.0.224)

		// 优先级高的可信头（针对阿里云 CDN）
		$trustedHeaders = [
			'HTTP_ALI_CDN_REAL_IP',     // 阿里云 CDN 真实 IP
			'HTTP_CF_CONNECTING_IP',    // Cloudflare（备用）
			'HTTP_X_FORWARDED_FOR',     // 通用代理头，取第一个 IP
		];

		foreach ($trustedHeaders as $key)
		{
			if (!empty($_SERVER[$key]))
			{
				if ($key === 'HTTP_X_FORWARDED_FOR')
				{
					$proxyIps = explode(',', $_SERVER[$key]);
					$ip = trim($proxyIps[0]); // 取第一个 IP 作为客户端 IP
				}
				else
				{
					$ip = trim($_SERVER[$key]);
				}
				break;
			}
		}
		dump($ip);
		die;
		$config = [
			'mchid' => '1977392152004120577',
			'appid' => '2021004124686129',
			'key_id' => 'IJ0CDFUTHRT38OGYXDESNLBJ',
			'key_secret' => 'HBMW3SLAHI8R5DZXP6HYW60NWWOJUY',
		];

		$service = new \app\service\api\DingxintongService($config);

		$order_id = '1978497723880468482';
		$order_no = '202510160746528524';

		$res = $service->bill_url($order_id, $order_no);
		dump($res);
		die;
		$alipayUrl = $res['data']['data']; // 你的完整URL

		$ch = curl_init();
		curl_setopt_array($ch, [
			CURLOPT_URL => $alipayUrl,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_SSL_VERIFYPEER => false
		]);
		$pdfContent = curl_exec($ch);
		file_put_contents($order_no . '.pdf', $pdfContent);
		echo file_exists($order_no . '.pdf') ? '✅ 下载成功！' : '❌ 下载失败';
		dd($res);
	}

	public function update_fee()
	{
		$order_id = intval(input('get.order_id')) ?? 0;

		$where = [];
		$where[] = ['id', '>', $order_id];

		$list = \app\model\Order::where($where)->limit(100)->order('id asc')->select();

		if (count($list) == 0)
		{
			dd('END');
		}

		foreach ($list as $order)
		{
			$info = json_decode($order->info, true);
			if (isset($info['agent_commission']))
			{
				$order->agent_commission = $info['agent_commission'] ?? 0;
				$order->agent_order_rate = $info['agent_order_rate'] ?? 0;
				$order->agent_order_fee = $info['agent_order_fee'] ?? 0;
				$order->card_commission = $info['card_commission'] ?? 0;
				$order->card_order_rate = $info['card_order_rate'] ?? 0;
				$order->card_order_fee = $info['card_order_fee'] ?? 0;
				$order->business_commission = $info['business_commission'] ?? 0;
				$order->business_order_rate = $info['business_order_rate'] ?? 0;
				$order->business_order_fee = $info['business_order_fee'] ?? 0;

				$order->save();
			}
		}

		// echo "<script>window.setTimeout(\"window.location.reload(true);\", 5000);</script>";

		echo "<script>window.setTimeout(\"window.location.href='?order_id={$order->id}';\", 1000);</script>";

		dd("num = " . count($list), "last_id = {$order->id}");
	}

	public function test()
	{
		$where = [];
		$where[] = ['channel_id', 'in', '1,2'];
		$where[] = ['business_id', '=', 30300];
		$where[] = ['status', '=', -1]; //状态：-1未支付 1成功，未回调 2成功，已回调 -2生成订单失败	
		$where[] = ['api_status', '=', 1]; //下单状态：-1未下单 1成功 -2失败
		$list = \app\model\Order::field('`channel_account_id`, COUNT(`id`) AS `num`')->where($where)->group('channel_account_id')->select()->column('num', 'channel_account_id');

		// dd(\app\model\Order::field('channel_account_id, SUM(id) as num')->where($where)->group('channel_account_id')->fetchSql(1)->select());

		dd($list);
	}
}
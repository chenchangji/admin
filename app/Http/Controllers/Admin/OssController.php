<?php

namespace App\Http\Controllers\Admin;

// use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use AlibabaCloud\Client\AlibabaCloud;
use AlibabaCloud\Client\Exception\ClientException;
use AlibabaCloud\Client\Exception\ServerException;
use AlibabaCloud\Sts\Sts;

class OssController extends Controller
{
    public function getClient()
    {
        // 从环境变量中获取步骤1.1生成的RAM用户的访问密钥（AccessKey ID和AccessKey Secret）。
        $accessKeyId =env('OSS_ACCESS_KEY_ID');
        $accessKeySecret = env('OSS_ACCESS_KEY_SECRET');
        // 从环境变量中获取步骤1.3生成的RAM角色的RamRoleArn。
        $roleArn = env('OSS_ARN');

        // 初始化阿里云客户端。
        AlibabaCloud::accessKeyClient($accessKeyId, $accessKeySecret)
            ->regionId('cn-shenzhen')
            ->asDefaultClient();

        try {
            // 创建STS请求。
            $result = Sts::v20150401()
                ->assumeRole()
                // 设置角色ARN。
                ->withRoleArn($roleArn)
                // 指定自定义角色会话名称，用来区分不同的令牌。
                ->withRoleSessionName('sessiontest')
                // 指定STS临时访问凭证过期时间为3600秒。
                ->withDurationSeconds(3600)
                ->request();

            // 获取响应中的凭证信息。
            $credentials = $result['Credentials'];
            $credentials['region'] = env('OSS_REGION');
            $credentials['bucket'] = env('OSS_BUCKET');
            return  $this->ok($credentials);
        } catch (ClientException $e) {
            // 处理客户端异常。
            echo $e->getErrorMessage() . PHP_EOL;
        } catch (ServerException $e) {
            // 处理服务端异常。
            echo $e->getErrorMessage() . PHP_EOL;
        }
    }

}

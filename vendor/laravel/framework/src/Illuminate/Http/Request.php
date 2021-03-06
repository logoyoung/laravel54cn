<?php

namespace Illuminate\Http;

use Closure;
use ArrayAccess;
use RuntimeException;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Macroable;
use Illuminate\Contracts\Support\Arrayable;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

class Request extends SymfonyRequest implements Arrayable, ArrayAccess
{
    use Concerns\InteractsWithContentTypes, // 与内容类型交互
        Concerns\InteractsWithFlashData, //与Flash数据交互
        Concerns\InteractsWithInput,//与输入交互
        Macroable;

    /**
     * The decoded JSON content for the request.
     *
     * 请求的解码JSON内容
     *
     * @var string
     */
    protected $json;

    /**
     * All of the converted files for the request.
     *
     * 请求的所有转换文件
     *
     * @var array
     */
    protected $convertedFiles;

    /**
     * The user resolver callback.
     *
     * 用户解析器回调
     *
     * @var \Closure
     */
    protected $userResolver;

    /**
     * The route resolver callback.
     *
     * 路由解析器回调
     *
     * @var \Closure
     */
    protected $routeResolver;

    /**
     * Create a new Illuminate HTTP request from server variables.
     *
     * 从服务器变量创建一个新的Illuminate HTTP请求
     *
     * @return static
     */
    public static function capture() //捕获
    {
        //设置Symfony允许请求参数重写
        static::enableHttpMethodParameterOverride();
        //创建一个Illuminate请求通过Symfony请求实例         从PHP的超级全局变量创建一个新的请求
        return static::createFromBase(SymfonyRequest::createFromGlobals());
    }

    /**
     * Return the Request instance.
     *
     * 返回请求实例
     *
     * @return $this
     */
    public function instance()
    {
        return $this;
    }

    /**
     * Get the request method.
     *
     * 获取请求的方法
     *
     * @return string
     */
    public function method()
    {
        //获取请求的“预期”方法
        return $this->getMethod();
    }

    /**
     * Get the root URL for the application.
     *
     * 获取应用程序的根URL
     *
     * @return string
     */
    public function root()
    {
        //                  获取协议和HTTP主机         返回执行此请求的根URL
        return rtrim($this->getSchemeAndHttpHost().$this->getBaseUrl(), '/');
    }

    /**
     * Get the URL (no query string) for the request.
     *
     * 从请求获取URL（无查询字符串）
     *
     * @return string
     */
    public function url()
    {
        //                                      为请求生成一个标准化URI（URL）
        return rtrim(preg_replace('/\?.*/', '', $this->getUri()), '/');
    }

    /**
     * Get the full URL for the request.
     *
     * 从请求获取完整的URL
     *
     * @return string
     */
    public function fullUrl()
    {
        $query = $this->getQueryString(); //为请求生成标准化查询字符串
        //           返回执行此请求的根URL  返回与被执行脚本相对应的路径
        $question = $this->getBaseUrl().$this->getPathInfo() == '/' ? '/?' : '?';
        //          从请求获取URL（无查询字符串）
        return $query ? $this->url().$question.$query : $this->url();
    }

    /**
     * Get the full URL for the request with the added query string parameters.
     *
     * 使用添加的查询字符串参数获取请求的完整URL
     *
     * @param  array  $query
     * @return string
     */
    public function fullUrlWithQuery(array $query)
    {
        //           返回执行此请求的根URL  返回与被执行脚本相对应的路径
        $question = $this->getBaseUrl().$this->getPathInfo() == '/' ? '/?' : '?';
        //         从请求中检索查询字符串项
        return count($this->query()) > 0
        //从请求获取URL（无查询字符串）                          从请求中检索查询字符串项
            ? $this->url().$question.http_build_query(array_merge($this->query(), $query))
            //从请求获取完整的URL
            : $this->fullUrl().$question.http_build_query($query);
    }

    /**
     * Get the current path info for the request.
     *
     * 获取请求的当前路径信息
     *
     * @return string
     */
    public function path()
    {
        //                返回与被执行脚本相对应的路径
        $pattern = trim($this->getPathInfo(), '/');

        return $pattern == '' ? '/' : $pattern;
    }

    /**
     * Get the current encoded path info for the request.
     *
     * 获取请求的当前编码路径信息
     *
     * @return string
     */
    public function decodedPath()
    {
        //  从 获取请求的当前路径信息 专用格式字符串还原成普通字符串
        return rawurldecode($this->path());
    }

    /**
     * Get a segment from the URI (1 based index).
     *
     * 从URI（1基于索引）获取段
     *
     * @param  int  $index
     * @param  string|null  $default
     * @return string|null
     */
    public function segment($index, $default = null)
    {
        //使用“点”符号从数组中获取一个项   获取请求路径的所有段
        return Arr::get($this->segments(), $index - 1, $default);
    }

    /**
     * Get all of the segments for the request path.
     *
     * 获取请求路径的所有段
     *
     * @return array
     */
    public function segments()
    {
        //                            获取请求的当前编码路径信息
        $segments = explode('/', $this->decodedPath());

        return array_values(array_filter($segments, function ($v) {
            return $v != '';
        }));
    }

    /**
     * Determine if the current request URI matches a pattern.
     *
     * 确定当前请求URI是否与模式匹配
     *
     * @return bool
     */
    public function is()
    {
        foreach (func_get_args() as $pattern) {
            //  确定给定的字符串是否与给定的模式匹配    获取请求的当前编码路径信息
            if (Str::is($pattern, $this->decodedPath())) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if the current request URL and query string matches a pattern.
     *
     * 确定当前的请求URL和查询字符串的匹配模式
     *
     * @return bool
     */
    public function fullUrlIs()
    {
        $url = $this->fullUrl();    //从请求获取完整的URL

        foreach (func_get_args() as $pattern) {
            if (Str::is($pattern, $url)) { //  确定给定的字符串是否与给定的模式匹配
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if the request is the result of an AJAX call.
     *
     * 确定请求是否是Ajax调用的结果
     *
     * @return bool
     */
    public function ajax()
    {
        return $this->isXmlHttpRequest(); //Symfony\Component\HttpFoundation\Request::isXmlHttpRequest
    }

    /**
     * Determine if the request is the result of an PJAX call.
     *
     * 确定该请求是否是一个pjax调用的结果
     *
     * @return bool
     */
    public function pjax()
    {
        //                  根据名称获取标头中的值
        return $this->headers->get('X-PJAX') == true;
    }

    /**
     * Determine if the request is over HTTPS.
     *
     * 确定请求是否是HTTPS
     *
     * @return bool
     */
    public function secure()
    {
        //检查请求是否安全
        return $this->isSecure();//Symfony\Component\HttpFoundation\Request::isSecure
    }

    /**
     * Returns the client IP address.
     *
     * 返回客户IP地址
     *
     * @return string
     */
    public function ip()
    {
        //返回客户端IP地址
        return $this->getClientIp(); //返回客户端IP地址 Symfony\Component\HttpFoundation\Request::getClientIp
    }

    /**
     * Returns the client IP addresses.
     *
     * 返回客户IP地址集合
     *
     * @return array
     */
    public function ips()
    {
        //返回客户端IP地址集合
        return $this->getClientIps();//返回客户端IP地址 Symfony\Component\HttpFoundation\Request::getClientIps
    }

    /**
     * Merge new input into the current request's input array.
     *
     * 将新输入合并到当前请求的输入数组中
     *
     * @param  array  $input
     * @return void
     */
    public function merge(array $input)
    {
        // 获取请求的输入源           添加参数数组Symfony\Component\HttpFoundation\ParameterBag::add
        $this->getInputSource()->add($input);
    }

    /**
     * Replace the input for the current request.
     *
     * 替换当前请求的输入
     *
     * @param  array  $input
     * @return void
     */
    public function replace(array $input)
    {
        //    获取请求的输入源      替换当前请求的输入
        $this->getInputSource()->replace($input);
    }

    /**
     * Get the JSON payload for the request.
     *
     * 获取请求的JSON有效载荷
     *
     * @param  string  $key
     * @param  mixed   $default
     * @return mixed
     */
    public function json($key = null, $default = null)
    {
        if (! isset($this->json)) {
            $this->json = new ParameterBag((array) json_decode($this->getContent(), true));
        }

        if (is_null($key)) {
            return $this->json;
        }
        //                     返回所有参数数组
        return data_get($this->json->all(), $key, $default);
    }

    /**
     * Get the input source for the request.
     *
     * 获取请求的输入源
     *
     * @return \Symfony\Component\HttpFoundation\ParameterBag
     */
    protected function getInputSource()
    {
        if ($this->isJson()) {
            return $this->json();
        }
        //返回 如果method是get，返回查询字符串，否则返回request对象
        return $this->getRealMethod() == 'GET' ? $this->query : $this->request;
    }

    /**
     * Create an Illuminate request from a Symfony instance.
     *
     * 从symfony实例创建一个Illuminate请求
     *
     * @param  \Symfony\Component\HttpFoundation\Request  $request
     * @return \Illuminate\Http\Request
     */
    public static function createFromBase(SymfonyRequest $request)
    {
        if ($request instanceof static) {
            return $request;
        }

        $content = $request->content;  // 保留content

        //调用symfony中的方法，克隆一个请求并覆盖它的一些参数
        $request = (new static)->duplicate(
            //         返回所有参数数组
            $request->query->all(), $request->request->all(), $request->attributes->all(),
            $request->cookies->all(), $request->files->all(), $request->server->all()
        );

        $request->content = $content;

        // 获取请求的输入源 get的$request->query,其他的$request->request
        $request->request = $request->getInputSource();

        return $request;
    }

    /**
     * {@inheritdoc}
     * 克隆一个请求并覆盖它的一些参数
     */
    public function duplicate(array $query = null, array $request = null, array $attributes = null, array $cookies = null, array $files = null, array $server = null)
    {
        //克隆一个请求并覆盖它的一些参数Symfony\Component\HttpFoundation\Request::duplicate   筛选给定的文件数组，移除任何空值
        return parent::duplicate($query, $request, $attributes, $cookies, $this->filterFiles($files), $server);
    }

    /**
     * Filter the given array of files, removing any empty values.
     *
     * 筛选给定的文件数组，移除任何空值
     *
     * @param  mixed  $files
     * @return mixed
     */
    protected function filterFiles($files)
    {
        if (! $files) {
            return;
        }

        foreach ($files as $key => $file) {
            if (is_array($file)) {
                //                 筛选给定的文件数组，移除任何空值
                $files[$key] = $this->filterFiles($files[$key]);
            }

            if (empty($files[$key])) {
                unset($files[$key]);
            }
        }

        return $files;
    }

    /**
     * Get the session associated with the request.
     *
     * 获取与请求关联的会话
     *
     * @return \Illuminate\Session\Store
     *
     * @throws \RuntimeException
     */
    public function session()
    {
        //一个请求包含session对象
        if (! $this->hasSession()) {
            throw new RuntimeException('Session store not set on request.');
        }
        //         获取session
        return $this->getSession();
    }

    /**
     * Set the session instance on the request.
     *
     * 在请求上设置session实例
     *
     * @param  \Illuminate\Contracts\Session\Session  $session
     * @return void
     */
    public function setLaravelSession($session)
    {
        $this->session = $session;
    }

    /**
     * Get the user making the request.
     *
     * 获取用户请求
     *
     * @param  string|null  $guard
     * @return mixed
     */
    public function user($guard = null)
    {
        //                      获取用户解析器回调
        return call_user_func($this->getUserResolver(), $guard);
    }

    /**
     * Get the route handling the request.
     *
     * 获取路由处理请求
     *
     * @param  string|null  $param
     *
     * @return \Illuminate\Routing\Route|object|string
     */
    public function route($param = null)
    {
        //                            获取路由解析器回调
        $route = call_user_func($this->getRouteResolver());

        if (is_null($route) || is_null($param)) {
            return $route;
        } else {
            return $route->parameter($param);
        }
    }

    /**
     * Get a unique fingerprint for the request / route / IP address.
     *
     * 获取请求/路由/ IP地址的唯一指纹
     *
     * @return string
     *
     * @throws \RuntimeException
     */
    public function fingerprint()
    {
        //       获取路由处理请求
        if (! $route = $this->route()) {
            throw new RuntimeException('Unable to generate fingerprint. Route unavailable.');
        }
        //组合请求的method、domain、uri、ip参数生成sha1串
        return sha1(implode('|', array_merge(
            //获取路由响应的HTTP请求method    获取路由定义的域 获取与路由关联的URI 返回客户IP地址
            $route->methods(), [$route->domain(), $route->uri(), $this->ip()]
        )));
    }

    /**
     * Set the JSON payload for the request.
     *
     * 设置请求的JSON有效载荷
     *
     * @param  array  $json
     * @return $this
     */
    public function setJson($json)
    {
        $this->json = $json;

        return $this;
    }

    /**
     * Get the user resolver callback.
     *
     * 获取用户解析器回调
     *
     * @return \Closure
     */
    public function getUserResolver()
    {
        return $this->userResolver ?: function () {
            //
        };
    }

    /**
     * Set the user resolver callback.
     *
     * 设置用户解析器回调
     *
     * @param  \Closure  $callback
     * @return $this
     */
    public function setUserResolver(Closure $callback)
    {
        $this->userResolver = $callback;

        return $this;
    }

    /**
     * Get the route resolver callback.
     *
     * 获取路由解析器回调
     *
     * @return \Closure
     */
    public function getRouteResolver()
    {
        return $this->routeResolver ?: function () {
            //
        };
    }

    /**
     * Set the route resolver callback.
     *
     * 设置路由回调解析器
     *
     * @param  \Closure  $callback
     * @return $this
     */
    public function setRouteResolver(Closure $callback)
    {
        $this->routeResolver = $callback;

        return $this;
    }

    /**
     * Get all of the input and files for the request.
     *
     * 获取请求的所有输入和文件
     *
     * @return array
     */
    public function toArray()
    {
        //获取请求的所有输入和文件
        return $this->all();
    }

    /**
     * Determine if the given offset exists.
     *
     * 确定给定偏移是否存在
     *
     * @param  string  $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        //                                 获取请求的所有输入和文件
        return array_key_exists($offset, $this->all());
    }

    /**
     * Get the value at the given offset.
     *
     * 得到给定偏移量的值
     *
     * @param  string  $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        //  使用“点”符号从数组或对象中获取项        获取请求的所有输入和文件
        return data_get($this->all(), $offset);
    }

    /**
     * Set the value at the given offset.
     *
     * 设置给定偏移量的值
     *
     * @param  string  $offset
     * @param  mixed  $value
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        //获取请求的输入源           设置参数通过key名
        $this->getInputSource()->set($offset, $value);
    }

    /**
     * Remove the value at the given offset.
     *
     * 删除给定偏移量的值
     *
     * @param  string  $offset
     * @return void
     */
    public function offsetUnset($offset)
    {
        //获取请求的输入源          删除指定的参数
        $this->getInputSource()->remove($offset);
    }

    /**
     * Check if an input element is set on the request.
     *
     * 检查输入元素是否设置在请求上
     *
     * @param  string  $key
     * @return bool
     */
    public function __isset($key)
    {
        //                      从请求中获取输入元素
        return ! is_null($this->__get($key));
    }

    /**
     * Get an input element from the request.
     *
     * 从请求中获取输入元素
     *
     * @param  string  $key
     * @return mixed
     */
    public function __get($key)
    {
        //      确定给定偏移是否存在
        if ($this->offsetExists($key)) {
            //        得到给定偏移量的值
            return $this->offsetGet($key);
        }
        //          获取路由处理请求
        return $this->route($key);
    }
}

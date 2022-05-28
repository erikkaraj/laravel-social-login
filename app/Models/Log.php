<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Log as LaravelLog;

class Log extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * @var array
     */
    protected $fillable = [
        'channel',
        'message',
        'method',
        'entity_class',
        'entity_id',
        'endpoint',
        'query',
        'client_ip',
        'user_agent',
        'user_id',
        'error',
        'extra'
    ];

    /**
     * Writes the info log
     *
     * @param string $message
     * @param string $method
     * @param object|mixed $entity
     * @param \Illuminate\Http\Request|null $request
     * @param \Throwable|null $exception
     * @param string $extra
     *
     * @return void
     */
    public static function info(
        string $message,
        string $method,
        object $entity,
        $request = null,
        $exception = null,
        $extra = ''
    ) {
        self::whoMakesThing('info', $message, $method, $entity, $request, $exception, $extra);
        self::writeFileLog('info', $message, $method, $entity, $request, $exception, $extra);
    }


    /**
     * Writes the warn log
     *
     * @param string $message
     * @param string $method
     * @param object|mixed $entity
     * @param \Illuminate\Http\Request|null $request
     * @param \Throwable|null $exception
     * @param string $extra
     *
     * @return void
     */
    public static function warn(
        string $message,
        string $method,
        object $entity,
        $request = null,
        $exception = null,
        $extra = ''
    ) {
        self::whoMakesThing('warn', $message, $method, $entity, $request, $exception, $extra);
        self::writeFileLog('warn', $message, $method, $entity, $request, $exception, $extra);
    }

    /**
     * Writes the error log
     *
     * @param string $message
     * @param string $method
     * @param object|mixed $entity
     * @param \Illuminate\Http\Request|null $request
     * @param \Throwable|null $exception
     * @param string $extra
     *
     * @return void
     */
    public static function error(
        string $message,
        string $method,
        object $entity,
        $request = null,
        $exception = null,
        $extra = ''
    ) {
        self::whoMakesThing('error', $message, $method, $entity, $request, $exception, $extra);
        self::writeFileLog('error', $message, $method, $entity, $request, $exception, $extra);
    }

    /**
     * Writes log on the database using model Log
     *
     * @param string $channel
     * @param string $message
     * @param string $method
     * @param object|mixed $entity
     * @param \Illuminate\Http\Request|null $request
     * @param \Throwable|null $exception
     * @param string $extra
     *
     * @return void
     */
    private static function whoMakesThing(
        string $channel,
        string $message,
        string $method,
        object $entity,
        $request = null,
        $exception = null,
        string $extra = ''
    ) {
        $context = self::getContext($channel, $message, $method, $entity, $request, $exception, $extra);

        Log::create([
            'channel' => $context['channel'],
            'message' => $context['message'],
            'method' => $context['method'],
            'entity_class' => $context['entity_class'],
            'entity_id' => $context['entity_id'],
            'endpoint' => $context['endpoint'],
            'query' => json_encode($context['query']),
            'client_ip' => $context['client_ip'],
            'user_agent' => $context['user_agent'],
            'user_id' => $context['user_id'],
            'error' => $context['error'],
            'extra' => $context['extra']
        ]);
    }

    /**
     * Writes on file log
     *
     * @param string $channel
     * @param string $message
     * @param string $method
     * @param object|mixed $entity
     * @param \Illuminate\Http\Request|null $request
     * @param \Throwable|null $exception
     * @param string $extra
     *
     * @return void
     */
    public static function writeFileLog(
        string $channel,
        string $message,
        string $method,
        object $entity,
        $request = null,
        $exception = null,
        string $extra = ''
    ) {
        $context = self::getContext(
            $channel,
            $message,
            $method,
            $entity,
            $request,
            $exception,
            $extra
        );

        $logMessage = implode(' | ', array_map(
            function ($v, $k) {
                if (is_array($v)) {
                    return $k . '[]=' . implode('&' . $k . '[]=', $v);
                } else {
                    return strtoupper($k) . " = \"$v\"";
                }
            },
            $context,
            array_keys($context)
        ));

        LaravelLog::{$channel}($logMessage);
    }


    /**
     * Get context for Logger
     *
     * @param string $channel
     * @param string $message
     * @param string $method
     * @param object|mixed $entity
     * @param \Illuminate\Http\Request|null $request
     * @param \Throwable|null $exception
     * @param string $extra
     *
     * @return array
     */
    private static function getContext(
        string $channel,
        string $message,
        string $method,
        object $entity,
        $request = null,
        $exception = null,
        string $extra = ''
    ): array {

        $methodExploded = explode('\\', $method);
        $method = end($methodExploded);

        $entityClass = is_null($entity) ? null : get_class($entity);

        $entityID = is_null($entity) ? null : (isset($entity->id) ? $entity->id : null);

        $endpoint = is_null($request) ? null : $request->url();

        $query = is_null($request) ? null : $request->query();

        $client_id = is_null($request) ? null : $request->ip();

        $userAgent = is_null($request) ? null : $request->url();

        $error = is_null($exception) ? null : $exception->getMessage();

        $userId = is_null($request)
            ? (is_null(auth()->user()) ? null : auth()->user()->id)
            : (is_null($request->user('api'))
                ? (is_null(auth()->user()) ? null : auth()->user()->id)
                : $request->user('api')->id);

        return [
            'channel' => $channel,
            'message' => $message,
            'method' => $method,
            'entity_class' => $entityClass,
            'entity_id' => $entityID,
            'endpoint' => $endpoint,
            'query' => $query,
            'client_ip' => $client_id,
            'user_agent' => $userAgent,
            'user_id' => $userId,
            'error' => $error,
            'extra' => $extra
        ];
    }
}

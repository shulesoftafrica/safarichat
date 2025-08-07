<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
use DB;

class Handler extends ExceptionHandler {

    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
            //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register() {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param  \Exception  $exception
     * @return void
     */
    function createLog($e, $notify_admins = FALSE) {
        $line = @$e->getTrace()[0]['line'];
        $err = "<br/><hr/><ul>\n";
        $err .= "\t<li>date time " . date('Y-M-d H:m', time()) . "</li>\n";
        $err .= "\t<li>Made By: " . session('id') . "</li>\n";
        $err .= "\t<li>usertype " . session('usertype') . "</li>\n";
        $err .= "\t<li>error msg: [" . $e->getCode() . '] ' . $e->getMessage() . ' on line ' . $line . ' of file ' . @$e->getTrace()[0]['file'] . "</li>\n";
        $err .= "\t<li>url: " . url()->current() . "</li>\n";
        $err .= "\t<li>Controller route: " . createRoute() . "</li>\n";
        $err .= "\t<li>Error from which host: " . gethostname() . "</li>\n";
        $err .= "\t<li>Error from username: " . session('username') . "</li>\n";
        $err .= "</ul>\n\n";

        $filename = '' . str_replace('-', '_', date('Y-M-d')) . '.html';
        error_log($err, 3, dirname(__FILE__) . "/../../storage/logs/" . $filename);


        if ($notify_admins == TRUE) {
            //$this->sendLog($err);
        }
    }

    function createErrorLog($e) {
        //  return 
       $this->createLog($e);
        $line = @$e->getTrace()[0]['line'];
        $object = [
            'error_message' => $e->getMessage() . ' on line ' . $line . ' of file ' . @$e->getTrace()[0]['file'],
            'file' => @$e->getTrace()[0]['file'],
            'route' => createRoute(),
            "url" => url()->current(),
            'error_instance' => get_class($e),
            'request' => json_encode(request()->all()),
            'created_by' => 0,
        ];

     //   \App\Models\ErrorLog::create($object);
//        if (preg_match('/api/i', url()->current())) {
//            //this might be the api requests
//            echo json_encode([
//                'status' => 500,
//                'message' => 'Server Error'
//            ]);
//        }
        return TRUE;
    }

    public function render($request, Throwable $exception) {

        $this->createErrorLog($exception);
//
//        if (preg_match('/does not exist on/', $exception->getMessage())) {
//            return redirect(url('dashboard'))->with('warning', 'Page Supplied with name does not exists and you have redirected to this home page. Please try to type correctly url or contact your administrator if problem persist');
//        }
//        if ($exception instanceof \Illuminate\Session\TokenMismatchException) {
//            return redirect()->back()->with('warning', 'This page has expired, please reload this page');
//        }
//
//        if ($exception instanceof ModelNotFoundException or $exception instanceof NotFoundHttpException) {
//
//            return $this->action(response()->view('errors.404', [], 404));
//        } else if ($exception instanceof QueryException) {
//
//            //catch Database Errors
//            return $this->databaseErrors($exception);
//        } else if ($exception instanceof FatalErrorException) {
//
//            return $this->action(redirect()->back()->with('error', "Sorry, we are experiencing difficulty processing your request "));
//        } else if ($exception instanceof \ErrorException) {
//
//
//            return $this->action(redirect()->back()->with('error', 'Sorry, we are experiencing difficulty processing your request'));
//        } else if ($exception instanceof \LogicException) {
//
//            return $this->action(response()->view('errors.fatal', [], 500));
//        }
        return parent::render($request, $exception);
    }

}

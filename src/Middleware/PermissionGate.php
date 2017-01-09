<?php

namespace Mashy\Permission\Middleware;

use Closure;
use Auth;

class PermissionGate
{
    private $request;
    
    /**
     * Handle an incoming request.
     * TODO: Correct JSON Response
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $this->request = $request;

        if(
            (! $this->getAction('is') or $this->hasRole()) and
            (! $this->getAction('can') or $this->hasPermission())
        ) {
            return $next($request);
        }
        if ($request->isJson() || $request->wantsJson()) {
            return response()->json([
                'error' => [
                    'status' => 401,
                    'code' => 'insufficientpermissions'
                ]
            ], 401);
        }

        return abort(401, 'You are not authorized to access this resource');
    }

    /**
     * Extract required action from requested route.
     *
     * @param string $key action name
     * @return string or false
     */
    protected function getAction($key)
    {
        $action = $this->request->route()->getAction();
        return isset($action[$key]) ? $action[$key] : false;
    }

    /**
     * Check if user has requested route role.
     *
     * @return bool
     */
    protected function hasRole()
    {
        $request = $this->request;
        $role = $this->getAction('is');

        return $request->user()->hasRole($role);
    }

    /**
     * Check if user has requested route permissions.
     *
     * @return bool
     */
    protected function hasPermission()
    {
        $request = $this->request;
        $do = $this->getAction('can');

        return $request->user()->can($do);
    }

}

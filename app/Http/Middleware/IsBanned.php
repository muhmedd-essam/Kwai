<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class IsBanned
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if(auth()->check() && auth()->user()->deactivated_until != null){
            $dUntil = auth()->user()->deactivated_until;
            auth()->logout(true);
            if($dUntil == '3000-01-01'){
                return response()->json('عفوا، حسابك محظور بشكل دائم. إذا كنت تعتقد أنك لم ترتكب أي مخالفة لسياسات إستخدام التطبيق؛ برجاء التواصل مع الدعم الفني.', 401);    
            }else{
                return response()->json('عفوا، حسابك محظور حتى تاريخ: ' .$dUntil . '. إذا كنت تعتقد أنك لم ترتكب أي مخالفة لسياسات إستخدام التطبيق؛ برجاء التواصل مع الدعم الفني.', 401);
            }
        }
        
        return $next($request);
    }
}

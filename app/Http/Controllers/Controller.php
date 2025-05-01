<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use OpenApi\Annotations as OA; 

/**
 * @OA\Info(
 *      version="1.0.0",
 *      title="RUFORUM MEL API",
 *      description="RUFORUM MEL API Documentation - V1.0.0",
 * )
 * @OA\Server(
 *      url="http://localhost/grad-track-api/api",
 *      description="Local server"
 * )
 * @OA\Server(
 *      url="https://skills-api.comfarnet.org/api",
 *      description="Production server"
 * )
 */
class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
}

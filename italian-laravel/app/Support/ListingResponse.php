<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ListingResponse
{
    public static function render(Request $request, string $name, array $data): Response
    {
        $view = view($name, $data);
        $fragment = $request->header('X-Listing-Fragment') === 'listing';
        $response = response($fragment ? $view->fragment('listing') : $view)
            ->header('Cache-Control', 'private, no-store')->header('Vary', 'X-Listing-Fragment');
        if ($fragment) {
            $response->header('X-Listing-Fragment', 'listing');
        }

        return $response;
    }
}

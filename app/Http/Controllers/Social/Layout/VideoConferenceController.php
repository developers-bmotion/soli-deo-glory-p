<?php

namespace App\Http\Controllers\Social\Layout;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class VideoConferenceController extends Controller
{
    public function index(){
        return view('social-network.pages.video-conference.video-conference');
    }
}

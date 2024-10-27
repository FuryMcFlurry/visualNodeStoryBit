<?php

namespace App\Http\Controllers;

use App\Models\Background;
use App\Models\Graph;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class NodeController extends Controller
{
    public function index()
    {
        $graph = Graph::first();

        if ($graph) {
            $graphData = json_decode($graph->data, true);
            return response()->json($graphData);
        }
    
        return response()->json(['error' => 'Graph not found'], 404);
    }

    public function store(Request $request)
    {
        $graphData = $request->input('data');

        if (is_array($graphData) || is_object($graphData)) {
            // Encode the data to JSON
            $formattedData = json_encode($graphData);
        } else {
            // Log an error if the data format is invalid
            Log::error('Invalid data format:', ['data' => $graphData]);
            return response()->json(['error' => 'Invalid data format'], 400);
        }

        $graph = Graph::first();
        if (!$graph) {
            $graph = new Graph();
            $graph->id = 1; // Ensure ID is set to 0
        }

        $graph->data = $formattedData;
        $graph->save();

        return response()->json(['message' => 'Graph saved successfully']);
    }

    public function showStoryCreator()
    {
        $graph = Graph::first();

        $graphData = $graph ? json_decode($graph->data, true) : null;
         return Inertia::render('StoryCreator/InteractionsApp', [
            'title' => 'Story Creator Page',
            'graphData' => $graphData,
            ]
         );
    }


    public function view()
    {
        $graph = Graph::first();

        $graphData = $graph ? json_decode($graph->data, true) : null;
         return Inertia::render('Adventure/View', [
            'title' => 'Adventure viewer',
            'graphData' => $graphData,
            ]
         );
    }

    public function saveProgress(Request $request)
    {
        $user = Auth::user();
        $user->game_progress = $request->input('progress');
        $user->save(); //somehow auth isn't known to my intelephense. ignore.

        return response()->json(['status' => 'success']);
    }

    public function loadProgress()
    {
        $user = Auth::user();

        return response()->json(['progress' => $user->game_progress]);
    }

//adventure maybe new model/controller?
    public function characterSelect()
    {
        $backstories = Background::all(); // Fetching all background characters from DB
        return Inertia::render('Adventure/characterSelector', [
            'title' => 'Character Creator',
            'backstories' => $backstories, // Passing to Vue component
        ]);
    }
    
    public function confirmSelection(Request $request)
    {
        $characterId = $request->input('characterId');
        $background = Background::find($characterId);

        if ($background) {
            return response()->json([
                'success' => true,
                'message' => "Character selection confirmed for character ID: {$characterId}!",
                'background' => $background, 
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => "No background found for character ID: {$characterId}."
            ], 404);
        }
    }

}

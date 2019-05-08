<?php

namespace R64\NovaFields\Http\Controllers;

use Laravel\Nova\Fields\FieldCollection;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Http\Controllers\AssociatableController as Controller;
use Yassi\NovaGroupField\NovaGroupField;

class AssociatableController extends Controller
{
    /**
     * List the available related resources for a given resource.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function index(NovaRequest $request)
    {
	    $fields = $request->newResource()->availableFields($request);
		$groupFieldsToMerge = new FieldCollection();
	    foreach ( $fields as $field ) {
		    if ($field instanceof NovaGroupField) {
			    $groupFields = $field->meta()['fields'];
			    $groupFieldsToMerge = $groupFieldsToMerge->merge($groupFields);
		    }
		}

	    $fields = $fields->merge($groupFieldsToMerge);
        $field = $fields->firstWhere('attribute', $request->field);

        if(!$field) {
            $rowField = $fields->firstWhere('component', 'nova-fields-row');
	        $fields = collect($rowField->meta['fields']);
	        $field = $fields->firstWhere('attribute', $request->field);
        }

        $withTrashed = $this->shouldIncludeTrashed(
            $request, $associatedResource = $field->resourceClass
        );

        return [
            'resources' => $field->buildAssociatableQuery($request, $withTrashed)->get()
                        ->mapInto($field->resourceClass)
                        ->filter->authorizedToAdd($request, $request->model())
                        ->map(function ($resource) use ($request, $field) {
                            return $field->formatAssociatableResource($request, $resource);
                        })->sortBy('display')->values(),
            'softDeletes' => $associatedResource::softDeletes(),
            'withTrashed' => $withTrashed,
        ];
    }
}

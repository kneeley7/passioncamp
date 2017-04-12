<?php

namespace App\Http\Controllers;

use App\Item;
use App\OrgItem;
use App\Organization;
use App\Jobs\DeployRoomsAndAssignToHotels;

class OrganizationItemController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('super');
    }

    public function create(Organization $organization)
    {
        $items = Item::orderBy('type')->orderBy('name')->get()->groupBy('type')->reverse();

        return view('admin.organization.item.create', compact(['organization', 'items']));
    }

    public function store(Organization $organization)
    {
        $item = Item::find(request('item'));

        $OrgItem = new OrgItem(request(['quantity']) + [
            'cost' => request('cost') * 100
        ]);
        $OrgItem->item()->associate($item);
        $OrgItem->organization()->associate($organization);
        $OrgItem->org_type = $item->type;
        $OrgItem->save();

        return redirect()->action('OrganizationController@show', $organization)->withSuccess('Item added.');
    }

    public function edit(Organization $organization, OrgItem $item)
    {
        return view('admin.organization.item.edit', compact('item', 'organization'));
    }

    public function update(Organization $organization, OrgItem $item)
    {
        $item->update(request(['quantity']) + [
            'cost' => request('cost') * 100
        ]);

        return redirect()->action('OrganizationController@show', $organization)->withSuccess('Item updated.');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\Firm;
use App\Models\Product;
use App\Models\ProductMaterial;
use App\Models\RawMaterial;
use App\Models\Region;
use App\Models\SubAssemblyComponent;
use App\Models\Supplier;
use App\Services\ActiveUserService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AdminController extends Controller
{
    protected $activeUserService;

    public function __construct(ActiveUserService $activeUserService)
    {
        $this->activeUserService = $activeUserService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function destroy($id)
    {
        //
    }

    /**
     * FUNCTION CREATED BY MUKESH
     */

    // CUSTOMIZED FUNCTION 01: GET ALL REGION.
    public function regions()
    {
        $regions = Region::all();

        return view('admin.regions', compact('regions'));
    }

    // CUSTOMIZED FUNCTION 02: GET ALL FIRMS.
    public function firms()
    {
        $firms = Firm::all();

        return view('admin.firms', compact('firms'));
    }

    // CUSTOMIZED FUNCTION 03: GET ALL SUPPLIERS.
    public function suppliers()
    {
        $suppliers = Supplier::with('supplierComponent.subAssemblyComponent')->get();

        return view('admin.suppliers', compact('suppliers'));
    }

    // CUSTOMIZED FUNCTION 04: GET ALL PRODUCTS.
    public function products()
    {
        // STEP 02: COLLECT PRODUCTS:
        $products = Product::with('industry')->get();
        $products->map(function ($product) {
            $product->materialtypes = ProductMaterial::select('type')->where('product_id', $product->id)->distinct()->get();
            $product->materialtypes->map(function ($materialtype) use ($product) {
                $materialtype->collections = ProductMaterial::where(['product_id' => $product->id, 'type' => $materialtype->type])->get();
                $materialtype->collections->map(function ($collection) use ($materialtype) {
                    if ($materialtype->type == 'raw_material') {
                        $collection->component = RawMaterial::where(['id' => $collection->material_id])->first();
                    } else {
                        $collection->component = SubAssemblyComponent::where(['id' => $collection->material_id])->first();
                    }

                    return $collection;
                });

                return $materialtype;
            });

            return $product;
        });

        return view('admin.products', compact('products'));
    }

    // CUSTOMIZED FUNCTION 05: GET ALL FACULTIES.
    public function users()
    {
        // Get currently logged-in admin
        $activeUserData = $this->activeUserService->getActiveUser();

        $activeUser = Admin::find($activeUserData->id);

        // Only allow super-admins to access this page
        if (! $activeUser || $activeUser->role !== 'super-admin') {
            abort(403, 'Unauthorized access.');
        }

        // Get all admins ordered by role descending
        $users = Admin::orderBy('role', 'desc')->get();

        return view('admin.users', compact('users'));
    }
}

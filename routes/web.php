<?php

use App\Http\Controllers\Cash\CashController;
use App\Http\Controllers\Cash\CashRegisterController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Finance\ReceivableController;
use App\Http\Controllers\Inventory\AdjustmentController;
use App\Http\Controllers\Inventory\CategoryController;
use App\Http\Controllers\Inventory\KardexController;
use App\Http\Controllers\Inventory\LotController;
use App\Http\Controllers\Inventory\ProductController;
use App\Http\Controllers\Inventory\TransferController;
use App\Http\Controllers\Inventory\WarehouseController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Purchases\PurchaseController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\Purchases\SupplierController;
use App\Http\Controllers\Sales\PosController;
use App\Http\Controllers\Sales\QuoteController;
use App\Http\Controllers\Sales\SaleController;
use App\Http\Controllers\Settings\BranchController;
use App\Http\Controllers\Settings\CompanyController;
use App\Http\Controllers\Settings\RoleController;
use App\Http\Controllers\Settings\SettingsController;
use App\Http\Controllers\Settings\SunatController;
use App\Http\Controllers\Settings\UserController;
use App\Http\Controllers\Sunat\ComprobanteController;
use App\Http\Controllers\Sunat\DespatchController;
use App\Http\Controllers\Sunat\LedgerController;
use App\Http\Controllers\Sunat\NoteController;
use App\Http\Controllers\Sunat\SummaryController;
use App\Http\Controllers\Sunat\VoidedController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth', 'verified', 'permission:inventario.gestionar'])->prefix('inventario')->name('inventario.')->group(function () {
    Route::resource('productos', ProductController::class)
        ->parameters(['productos' => 'product'])
        ->except(['show']);

    Route::resource('categorias', CategoryController::class)
        ->parameters(['categorias' => 'category'])
        ->only(['index', 'store', 'update', 'destroy']);

    Route::resource('almacenes', WarehouseController::class)
        ->parameters(['almacenes' => 'warehouse'])
        ->only(['index', 'store', 'update', 'destroy']);

    Route::get('kardex', [KardexController::class, 'index'])->name('kardex.index');
    Route::get('kardex/{product}', [KardexController::class, 'show'])->name('kardex.show');

    Route::get('ajustes', [AdjustmentController::class, 'index'])->name('ajustes.index');
    Route::post('ajustes', [AdjustmentController::class, 'store'])->name('ajustes.store');
    Route::get('ajustes/stock-actual', [AdjustmentController::class, 'stockActual'])->name('ajustes.stock-actual');

    Route::get('transferencias', [TransferController::class, 'index'])->name('transferencias.index');
    Route::get('transferencias/crear', [TransferController::class, 'create'])->name('transferencias.create');
    Route::post('transferencias', [TransferController::class, 'store'])->name('transferencias.store');
    Route::get('transferencias/buscar-productos', [TransferController::class, 'buscarProductos'])->name('transferencias.buscar-productos');
    Route::get('transferencias/{transfer}', [TransferController::class, 'show'])->name('transferencias.show');

    Route::get('lotes', [LotController::class, 'index'])->name('lotes.index');
    Route::get('lotes/buscar-productos', [LotController::class, 'buscarProductos'])->name('lotes.buscar-productos');
    Route::post('lotes', [LotController::class, 'store'])->name('lotes.store');
    Route::post('lotes/{lot}/merma', [LotController::class, 'merma'])->name('lotes.merma');
});

Route::middleware(['auth', 'verified', 'permission:ventas.gestionar'])->prefix('clientes')->name('clientes.')->group(function () {
    Route::get('/', [CustomerController::class, 'index'])->name('index');
    Route::post('/', [CustomerController::class, 'store'])->name('store');
    Route::get('consultar-documento', [CustomerController::class, 'consultar'])->name('consultar');
    Route::get('buscar', [CustomerController::class, 'buscar'])->name('buscar');
    Route::post('rapido', [CustomerController::class, 'quickStore'])->name('rapido');
    Route::put('{customer}', [CustomerController::class, 'update'])->name('update');
    Route::delete('{customer}', [CustomerController::class, 'destroy'])->name('destroy');
});

Route::middleware(['auth', 'verified', 'permission:ventas.gestionar'])->prefix('ventas')->name('ventas.')->group(function () {
    Route::get('pos', [PosController::class, 'create'])->name('pos');
    Route::post('pos', [PosController::class, 'store'])->name('pos.store');
    Route::get('buscar-productos', [PosController::class, 'buscarProductos'])->name('buscar-productos');
    Route::get('buscar-clientes', [PosController::class, 'buscarClientes'])->name('buscar-clientes');
    Route::get('/', [SaleController::class, 'index'])->name('index');
    Route::get('{sale}/pdf', [SaleController::class, 'pdf'])->name('pdf');
    Route::get('{sale}', [SaleController::class, 'show'])->name('show');
});

Route::middleware(['auth', 'verified', 'permission:ventas.gestionar'])->group(function () {
    Route::get('cotizaciones', [QuoteController::class, 'index'])->defaults('tipo', 'cotizacion')->name('cotizaciones.index');
    Route::get('cotizaciones/crear', [QuoteController::class, 'create'])->defaults('tipo', 'cotizacion')->name('cotizaciones.create');
    Route::get('pedidos', [QuoteController::class, 'index'])->defaults('tipo', 'pedido')->name('pedidos.index');
    Route::get('pedidos/crear', [QuoteController::class, 'create'])->defaults('tipo', 'pedido')->name('pedidos.create');

    Route::prefix('documentos-venta')->name('documentos.')->group(function () {
        Route::get('buscar-productos', [QuoteController::class, 'buscarProductos'])->name('buscar-productos');
        Route::get('buscar-clientes', [QuoteController::class, 'buscarClientes'])->name('buscar-clientes');
        Route::post('/', [QuoteController::class, 'store'])->name('store');
        Route::get('{quote}', [QuoteController::class, 'show'])->name('show');
        Route::post('{quote}/convertir', [QuoteController::class, 'convertir'])->name('convertir');
        Route::patch('{quote}/estado', [QuoteController::class, 'estado'])->name('estado');
        Route::delete('{quote}', [QuoteController::class, 'destroy'])->name('destroy');
    });
});

Route::middleware(['auth', 'verified', 'permission:compras.gestionar'])->prefix('compras')->name('compras.')->group(function () {
    // Proveedores
    Route::get('proveedores', [SupplierController::class, 'index'])->name('proveedores.index');
    Route::post('proveedores', [SupplierController::class, 'store'])->name('proveedores.store');
    Route::get('proveedores/consultar-documento', [SupplierController::class, 'consultar'])->name('proveedores.consultar');
    Route::get('proveedores/buscar', [SupplierController::class, 'buscar'])->name('proveedores.buscar');
    Route::put('proveedores/{supplier}', [SupplierController::class, 'update'])->name('proveedores.update');
    Route::delete('proveedores/{supplier}', [SupplierController::class, 'destroy'])->name('proveedores.destroy');

    // Compras
    Route::get('/', [PurchaseController::class, 'index'])->name('index');
    Route::get('crear', [PurchaseController::class, 'create'])->name('create');
    Route::post('/', [PurchaseController::class, 'store'])->name('store');
    Route::get('buscar-productos', [PurchaseController::class, 'buscarProductos'])->name('buscar-productos');
    Route::get('cuentas-por-pagar', [PurchaseController::class, 'payables'])->name('payables');
    Route::post('{purchase}/pagos', [PurchaseController::class, 'pagar'])->name('pagar');
    Route::get('{purchase}/pdf', [PurchaseController::class, 'pdf'])->name('pdf');
    Route::get('{purchase}', [PurchaseController::class, 'show'])->name('show');
});

Route::middleware(['auth', 'verified', 'permission:caja.gestionar'])->prefix('caja')->name('caja.')->group(function () {
    Route::get('/', [CashController::class, 'index'])->name('index');
    Route::post('abrir', [CashController::class, 'abrir'])->name('abrir');
    Route::get('flujo', [CashController::class, 'flujo'])->name('flujo');

    Route::post('cajas', [CashRegisterController::class, 'store'])->name('cajas.store');
    Route::put('cajas/{cashRegister}', [CashRegisterController::class, 'update'])->name('cajas.update');
    Route::delete('cajas/{cashRegister}', [CashRegisterController::class, 'destroy'])->name('cajas.destroy');

    Route::get('{cashSession}', [CashController::class, 'show'])->name('show');
    Route::post('{cashSession}/movimientos', [CashController::class, 'movimiento'])->name('movimiento');
    Route::post('{cashSession}/cerrar', [CashController::class, 'cerrar'])->name('cerrar');
});

Route::middleware(['auth', 'verified', 'permission:caja.gestionar'])->prefix('cuentas-por-cobrar')->name('cxc.')->group(function () {
    Route::get('/', [ReceivableController::class, 'index'])->name('index');
    Route::post('{sale}/cobrar', [ReceivableController::class, 'cobrar'])->name('cobrar');
});

Route::middleware(['auth', 'verified', 'permission:ventas.gestionar'])->prefix('comprobantes')->name('comprobantes.')->group(function () {
    Route::get('/', [ComprobanteController::class, 'index'])->name('index');
    Route::post('{sale}/emitir', [ComprobanteController::class, 'emitir'])->name('emitir');
    Route::get('{sale}/xml', [ComprobanteController::class, 'xml'])->name('xml');
    Route::get('{sale}/cdr', [ComprobanteController::class, 'cdr'])->name('cdr');
});

Route::middleware(['auth', 'verified', 'permission:ventas.gestionar'])->prefix('resumenes')->name('resumenes.')->group(function () {
    Route::get('/', [SummaryController::class, 'index'])->name('index');
    Route::post('generar', [SummaryController::class, 'generar'])->name('generar');
    Route::post('{boletaSummary}/consultar', [SummaryController::class, 'consultar'])->name('consultar');
    Route::get('{boletaSummary}/xml', [SummaryController::class, 'xml'])->name('xml');
    Route::get('{boletaSummary}/cdr', [SummaryController::class, 'cdr'])->name('cdr');
});

Route::middleware(['auth', 'verified', 'permission:ventas.gestionar'])->prefix('bajas')->name('bajas.')->group(function () {
    Route::get('/', [VoidedController::class, 'index'])->name('index');
    Route::get('buscar-facturas', [VoidedController::class, 'buscarFacturas'])->name('buscar-facturas');
    Route::post('anular', [VoidedController::class, 'anular'])->name('anular');
    Route::post('{voidedDocument}/reenviar', [VoidedController::class, 'reenviar'])->name('reenviar');
    Route::post('{voidedDocument}/consultar', [VoidedController::class, 'consultar'])->name('consultar');
    Route::get('{voidedDocument}/xml', [VoidedController::class, 'xml'])->name('xml');
    Route::get('{voidedDocument}/cdr', [VoidedController::class, 'cdr'])->name('cdr');
});

Route::middleware(['auth', 'verified', 'permission:ventas.gestionar'])->prefix('notas')->name('notas.')->group(function () {
    Route::get('/', [NoteController::class, 'index'])->name('index');
    Route::post('/', [NoteController::class, 'store'])->name('store');
    Route::get('buscar-ventas', [NoteController::class, 'buscarVentas'])->name('buscar-ventas');
    Route::post('{note}/emitir', [NoteController::class, 'emitir'])->name('emitir');
    Route::get('{note}/xml', [NoteController::class, 'xml'])->name('xml');
    Route::get('{note}/cdr', [NoteController::class, 'cdr'])->name('cdr');
    Route::get('{note}/pdf', [NoteController::class, 'pdf'])->name('pdf');
});

Route::middleware(['auth', 'verified', 'permission:ventas.gestionar'])->prefix('guias')->name('guias.')->group(function () {
    Route::get('/', [DespatchController::class, 'index'])->name('index');
    Route::get('crear', [DespatchController::class, 'create'])->name('create');
    Route::post('/', [DespatchController::class, 'store'])->name('store');
    Route::get('buscar-ventas', [DespatchController::class, 'buscarVentas'])->name('buscar-ventas');
    Route::post('{despatch}/emitir', [DespatchController::class, 'emitir'])->name('emitir');
    Route::post('{despatch}/consultar', [DespatchController::class, 'consultar'])->name('consultar');
    Route::get('{despatch}/pdf', [DespatchController::class, 'pdf'])->name('pdf');
    Route::get('{despatch}/xml', [DespatchController::class, 'xml'])->name('xml');
    Route::get('{despatch}/cdr', [DespatchController::class, 'cdr'])->name('cdr');
});

Route::middleware(['auth', 'verified'])->prefix('configuracion')->name('settings.')->group(function () {
    Route::get('/', [SettingsController::class, 'index'])->name('index');
});

Route::middleware(['auth', 'verified', 'permission:configuracion.gestionar'])->prefix('configuracion')->name('settings.')->group(function () {
    Route::get('empresa', [CompanyController::class, 'edit'])->name('empresa.edit');
    Route::post('empresa', [CompanyController::class, 'update'])->name('empresa.update');

    Route::get('sunat', [SunatController::class, 'edit'])->name('sunat.edit');
    Route::post('sunat', [SunatController::class, 'update'])->name('sunat.update');

    Route::resource('sucursales', BranchController::class)
        ->parameters(['sucursales' => 'branch'])
        ->only(['index', 'store', 'update', 'destroy']);
});

Route::middleware(['auth', 'verified', 'permission:reportes.ver'])->prefix('libros')->name('libros.')->group(function () {
    Route::get('/', [LedgerController::class, 'index'])->name('index');
    Route::get('ventas', [LedgerController::class, 'ventas'])->name('ventas');
    Route::get('compras', [LedgerController::class, 'compras'])->name('compras');
});

Route::middleware(['auth', 'verified', 'permission:reportes.ver'])->prefix('reportes')->name('reportes.')->group(function () {
    Route::get('/', [ReportController::class, 'index'])->name('index');
    Route::get('{key}/exportar', [ReportController::class, 'export'])->name('export')->where('key', '[a-z\-]+');
    Route::get('{key}', [ReportController::class, 'show'])->name('show')->where('key', '[a-z\-]+');
});

Route::middleware(['auth', 'verified', 'permission:usuarios.gestionar'])->prefix('configuracion')->name('settings.')->group(function () {
    Route::get('usuarios', [UserController::class, 'index'])->name('usuarios.index');
    Route::post('usuarios', [UserController::class, 'store'])->name('usuarios.store');
    Route::put('usuarios/{user}', [UserController::class, 'update'])->name('usuarios.update');
    Route::delete('usuarios/{user}', [UserController::class, 'destroy'])->name('usuarios.destroy');

    Route::get('roles', [RoleController::class, 'index'])->name('roles.index');
    Route::post('roles', [RoleController::class, 'store'])->name('roles.store');
    Route::put('roles/{role}', [RoleController::class, 'update'])->name('roles.update');
    Route::delete('roles/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

<?php

namespace Database\Seeders;

use App\Models\BoletaSummary;
use App\Models\Branch;
use App\Models\CashRegister;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Supplier;
use App\Models\User;
use App\Models\VoidedDocument;
use App\Models\Warehouse;
use App\Services\CashService;
use App\Services\DespatchService;
use App\Services\LotService;
use App\Services\NoteService;
use App\Services\PurchaseService;
use App\Services\QuoteService;
use App\Services\SaleService;
use App\Services\TransferService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/**
 * Datos de demostración para los módulos transaccionales (ventas, compras,
 * cotizaciones, transferencias, notas, guías, lotes, caja) que los seeders
 * de catálogo no cubren. Usa los Services de la app para que las reglas de
 * negocio (kardex, totales, caja) queden consistentes entre sí.
 */
class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::first();
        if (! $company) {
            return;
        }

        $mainBranch = $company->branches()->where('is_main', true)->first();
        $norteBranch = $company->branches()->where('is_main', false)->first();

        $users = $this->seedUsers($company, $mainBranch, $norteBranch);
        [$almacen1, $almacen2] = [
            Warehouse::where('company_id', $company->id)->where('codigo', 'ALM01')->first(),
            Warehouse::where('company_id', $company->id)->where('codigo', 'ALM02')->first(),
        ];

        if (! $almacen1 || ! $almacen2) {
            return;
        }

        $sales = $this->seedCashAndSales($company, $users, $almacen1);
        $this->seedPurchases($users, $almacen1);
        $this->seedQuotes($users);
        $this->seedTransfers($users, $almacen1, $almacen2);
        $this->seedLots($users, $almacen1, $almacen2);
        $this->seedNotes($users, $sales);
        $this->seedDespatches($company, $users, $sales, $mainBranch, $norteBranch);
        $this->seedBoletaSummaries($company, $users['admin']);
        $this->seedVoidedDocuments($company, $users['admin'], $sales);

        Auth::logout();
    }

    /** @return array<string, User> */
    private function seedUsers(Company $company, ?Branch $mainBranch, ?Branch $norteBranch): array
    {
        $definiciones = [
            'vendedor' => ['email' => 'vendedor@erp.test', 'name' => 'Lucía Vendedor', 'position' => 'Vendedora', 'role' => 'Vendedor', 'branch' => $mainBranch],
            'almacenero' => ['email' => 'almacenero@erp.test', 'name' => 'Miguel Almacenero', 'position' => 'Almacenero', 'role' => 'Almacenero', 'branch' => $mainBranch],
            'cajero' => ['email' => 'cajero@erp.test', 'name' => 'Rosa Cajera', 'position' => 'Cajera', 'role' => 'Cajero', 'branch' => $mainBranch],
            'contador' => ['email' => 'contador@erp.test', 'name' => 'Jorge Contador', 'position' => 'Contador', 'role' => 'Contador', 'branch' => $norteBranch],
        ];

        $users = ['admin' => User::where('email', 'admin@erp.test')->firstOrFail()];

        foreach ($definiciones as $key => $def) {
            $user = User::firstOrCreate(
                ['email' => $def['email']],
                [
                    'company_id' => $company->id,
                    'branch_id' => $def['branch']?->id,
                    'name' => $def['name'],
                    'position' => $def['position'],
                    'is_active' => true,
                    'email_verified_at' => now(),
                    'password' => Hash::make('password'),
                ],
            );
            $user->syncRoles([$def['role']]);
            $users[$key] = $user;
        }

        return $users;
    }

    /** @return array<int, Sale> */
    private function seedCashAndSales(Company $company, array $users, Warehouse $almacen): array
    {
        $cash = app(CashService::class);
        $salesSvc = app(SaleService::class);

        $cajaPrincipal = CashRegister::where('company_id', $company->id)->where('nombre', 'Caja Principal')->first();
        $cajaNorte = CashRegister::where('company_id', $company->id)->where('nombre', 'Caja Sucursal Norte')->first();

        $arroz = Product::where('company_id', $company->id)->where('codigo', 'P0001')->first();
        $gaseosa = Product::where('company_id', $company->id)->where('codigo', 'P0003')->first();
        $leche = Product::where('company_id', $company->id)->where('codigo', 'P0005')->first();
        $fideos = Product::where('company_id', $company->id)->where('codigo', 'P0008')->first();
        $aceite = Product::where('company_id', $company->id)->where('codigo', 'P0002')->first();
        $jabon = Product::where('company_id', $company->id)->where('codigo', 'P0006')->first();

        $maria = Customer::where('company_id', $company->id)->where('numero_documento', '45678912')->first();
        $carlos = Customer::where('company_id', $company->id)->where('numero_documento', '70123456')->first();
        $ramirez = Customer::where('company_id', $company->id)->where('numero_documento', '20512345678')->first();
        $distribuidoraSur = Customer::where('company_id', $company->id)->where('numero_documento', '20487654321')->first();

        $sales = [];

        // --- Sesión de caja 1 (Principal, hace 4 días, cerrada) ---
        Auth::login($users['cajero']);
        $sesion1 = $cash->abrir($cajaPrincipal, 100);
        $sesion1->update(['fecha_apertura' => now()->subDays(4)->setTime(8, 0)]);

        $sales[1] = $salesSvc->registrar([
            'tipo_comprobante' => 'boleta',
            'warehouse_id' => $almacen->id,
            'metodo_pago' => 'efectivo',
            'items' => [
                ['product_id' => $arroz->id, 'cantidad' => 2, 'precio_unitario' => (float) $arroz->precio_venta],
                ['product_id' => $gaseosa->id, 'cantidad' => 1, 'precio_unitario' => (float) $gaseosa->precio_venta],
            ],
        ]);
        $cash->registrarIngresoVenta($sales[1]);
        $cash->registrarMovimiento($sesion1, 'egreso', 'gasto', 'Compra de útiles de limpieza', 25);
        $sesion1 = $cash->cerrar($sesion1, $sesion1->saldoEsperado());
        $sesion1->update(['fecha_cierre' => now()->subDays(4)->setTime(20, 0)]);

        // --- Sesión de caja 2 (Principal, hace 2 días, cerrada) ---
        $sesion2 = $cash->abrir($cajaPrincipal, 120);
        $sesion2->update(['fecha_apertura' => now()->subDays(2)->setTime(8, 0)]);

        $sales[2] = $salesSvc->registrar([
            'tipo_comprobante' => 'boleta',
            'customer_id' => $maria->id,
            'warehouse_id' => $almacen->id,
            'metodo_pago' => 'yape',
            'items' => [
                ['product_id' => $leche->id, 'cantidad' => 3, 'precio_unitario' => (float) $leche->precio_venta],
            ],
        ]);
        $cash->registrarMovimiento($sesion2, 'ingreso', 'ajuste', 'Ajuste de caja chica', 20);
        $cash->registrarMovimiento($sesion2, 'egreso', 'retiro', 'Retiro a bóveda', 15);
        $sesion2 = $cash->cerrar($sesion2, $sesion2->saldoEsperado());
        $sesion2->update(['fecha_cierre' => now()->subDays(2)->setTime(20, 0)]);

        // --- Sesión de caja 3 (Principal, hoy, queda abierta) ---
        $sesion3 = $cash->abrir($cajaPrincipal, 200, 'Apertura del turno de hoy');

        $sales[3] = $salesSvc->registrar([
            'tipo_comprobante' => 'factura',
            'customer_id' => $ramirez->id,
            'warehouse_id' => $almacen->id,
            'metodo_pago' => 'transferencia',
            'items' => [
                ['product_id' => $fideos->id, 'cantidad' => 10, 'precio_unitario' => (float) $fideos->precio_venta],
                ['product_id' => $aceite->id, 'cantidad' => 5, 'precio_unitario' => (float) $aceite->precio_venta],
                ['product_id' => $jabon->id, 'cantidad' => 12, 'precio_unitario' => (float) $jabon->precio_venta],
            ],
        ]);

        $sales[4] = $salesSvc->registrar([
            'tipo_comprobante' => 'boleta',
            'customer_id' => $carlos->id,
            'warehouse_id' => $almacen->id,
            'metodo_pago' => 'tarjeta',
            'items' => [
                ['product_id' => $gaseosa->id, 'cantidad' => 2, 'precio_unitario' => (float) $gaseosa->precio_venta],
                ['product_id' => $arroz->id, 'cantidad' => 1, 'precio_unitario' => (float) $arroz->precio_venta],
            ],
        ]);

        $sales[5] = $salesSvc->registrar([
            'tipo_comprobante' => 'factura',
            'customer_id' => $distribuidoraSur->id,
            'warehouse_id' => $almacen->id,
            'metodo_pago' => 'credito',
            'items' => [
                ['product_id' => $leche->id, 'cantidad' => 20, 'precio_unitario' => (float) $leche->precio_venta],
                ['product_id' => $fideos->id, 'cantidad' => 15, 'precio_unitario' => (float) $fideos->precio_venta],
            ],
        ]);
        // Cobro parcial de la venta a crédito (genera sale_payments y un ingreso de caja).
        $cash->registrarCobro($sales[5], round((float) $sales[5]->total * 0.4, 2), 'efectivo');
        $cash->registrarMovimiento($sesion3, 'egreso', 'retiro', 'Retiro parcial a bóveda', 50);

        // --- Sesiones de la Sucursal Norte (usuario vendedor) ---
        Auth::login($users['vendedor']);
        $sesionNorte1 = $cash->abrir($cajaNorte, 80);
        $sesionNorte1->update(['fecha_apertura' => now()->subDays(3)->setTime(9, 0)]);
        $cash->registrarMovimiento($sesionNorte1, 'ingreso', 'ajuste', 'Fondo fijo adicional', 20);
        $cash->registrarMovimiento($sesionNorte1, 'egreso', 'gasto', 'Movilidad y encomienda', 15);
        $sesionNorte1 = $cash->cerrar($sesionNorte1, $sesionNorte1->saldoEsperado());
        $sesionNorte1->update(['fecha_cierre' => now()->subDays(3)->setTime(19, 0)]);

        $sesionNorte2 = $cash->abrir($cajaNorte, 100, 'Apertura del turno de hoy');
        $cash->registrarMovimiento($sesionNorte2, 'ingreso', 'deposito', 'Depósito de refuerzo', 50);

        Auth::logout();

        return $sales;
    }

    private function seedPurchases(array $users, Warehouse $almacen): void
    {
        Auth::login($users['almacenero']);
        $purchases = app(PurchaseService::class);
        $company = $users['almacenero']->company;

        $mayorista = Supplier::where('company_id', $company->id)->where('numero_documento', '20100050678')->first();
        $importaciones = Supplier::where('company_id', $company->id)->where('numero_documento', '20557788990')->first();
        $limaNorte = Supplier::where('company_id', $company->id)->where('numero_documento', '20602233445')->first();
        $industriasSur = Supplier::where('company_id', $company->id)->where('numero_documento', '20445566778')->first();
        $luisHerrera = Supplier::where('company_id', $company->id)->where('numero_documento', '41122334')->first();

        $arroz = Product::where('company_id', $company->id)->where('codigo', 'P0001')->first();
        $aceite = Product::where('company_id', $company->id)->where('codigo', 'P0002')->first();
        $gaseosa = Product::where('company_id', $company->id)->where('codigo', 'P0003')->first();
        $detergente = Product::where('company_id', $company->id)->where('codigo', 'P0004')->first();
        $leche = Product::where('company_id', $company->id)->where('codigo', 'P0005')->first();

        $p1 = $purchases->registrar([
            'tipo_comprobante' => 'factura',
            'serie' => 'F250', 'numero' => '001523',
            'supplier_id' => $mayorista->id,
            'warehouse_id' => $almacen->id,
            'fecha' => now()->subDays(6)->toDateString(),
            'condicion_pago' => 'contado',
            'metodo_pago' => 'efectivo',
            'items' => [
                ['product_id' => $arroz->id, 'cantidad' => 50, 'costo_unitario' => 21.50],
                ['product_id' => $aceite->id, 'cantidad' => 30, 'costo_unitario' => 12.00],
            ],
        ]);

        $p2 = $purchases->registrar([
            'tipo_comprobante' => 'boleta',
            'serie' => 'B180', 'numero' => '004410',
            'supplier_id' => $importaciones->id,
            'warehouse_id' => $almacen->id,
            'fecha' => now()->subDays(5)->toDateString(),
            'fecha_vencimiento' => now()->addDays(25)->toDateString(),
            'condicion_pago' => 'credito',
            'items' => [
                ['product_id' => $gaseosa->id, 'cantidad' => 40, 'costo_unitario' => 13.80],
            ],
        ]);

        $p3 = $purchases->registrar([
            'tipo_comprobante' => 'factura',
            'serie' => 'F102', 'numero' => '008832',
            'supplier_id' => $limaNorte->id,
            'warehouse_id' => $almacen->id,
            'fecha' => now()->subDays(3)->toDateString(),
            'condicion_pago' => 'contado',
            'metodo_pago' => 'transferencia',
            'items' => [
                ['product_id' => $detergente->id, 'cantidad' => 20, 'costo_unitario' => 12.60],
            ],
        ]);

        $p4 = $purchases->registrar([
            'tipo_comprobante' => 'factura',
            'serie' => 'F330', 'numero' => '002290',
            'supplier_id' => $industriasSur->id,
            'warehouse_id' => $almacen->id,
            'fecha' => now()->subDays(2)->toDateString(),
            'fecha_vencimiento' => now()->addDays(15)->toDateString(),
            'condicion_pago' => 'credito',
            'items' => [
                ['product_id' => $leche->id, 'cantidad' => 60, 'costo_unitario' => 8.70],
            ],
        ]);

        $purchases->registrar([
            'tipo_comprobante' => 'boleta',
            'serie' => 'B045', 'numero' => '001177',
            'supplier_id' => $luisHerrera->id,
            'warehouse_id' => $almacen->id,
            'fecha' => now()->subDay()->toDateString(),
            'condicion_pago' => 'contado',
            'metodo_pago' => 'efectivo',
            'items' => [
                ['product_id' => $arroz->id, 'cantidad' => 10, 'costo_unitario' => 22.00],
            ],
        ]);

        // Pagos adicionales a las compras a crédito.
        $purchases->registrarPago($p2, round((float) $p2->total * 0.5, 2), 'efectivo');
        $purchases->registrarPago($p4, (float) $p4->total, 'transferencia');

        Auth::logout();
    }

    private function seedQuotes(array $users): void
    {
        Auth::login($users['vendedor']);
        $quotes = app(QuoteService::class);
        $company = $users['vendedor']->company;

        $maria = Customer::where('company_id', $company->id)->where('numero_documento', '45678912')->first();
        $carlos = Customer::where('company_id', $company->id)->where('numero_documento', '70123456')->first();
        $ramirez = Customer::where('company_id', $company->id)->where('numero_documento', '20512345678')->first();
        $distribuidoraSur = Customer::where('company_id', $company->id)->where('numero_documento', '20487654321')->first();
        $pedro = Customer::where('company_id', $company->id)->where('numero_documento', '10234567')->first();

        $arroz = Product::where('company_id', $company->id)->where('codigo', 'P0001')->first();
        $aceite = Product::where('company_id', $company->id)->where('codigo', 'P0002')->first();
        $gaseosa = Product::where('company_id', $company->id)->where('codigo', 'P0003')->first();
        $leche = Product::where('company_id', $company->id)->where('codigo', 'P0005')->first();
        $fideos = Product::where('company_id', $company->id)->where('codigo', 'P0008')->first();

        $quotes->registrar([
            'tipo' => 'cotizacion',
            'customer_id' => $maria->id,
            'fecha' => now()->subDays(3)->toDateString(),
            'fecha_validez' => now()->addDays(7)->toDateString(),
            'items' => [
                ['product_id' => $arroz->id, 'cantidad' => 5, 'precio_unitario' => (float) $arroz->precio_venta],
                ['product_id' => $aceite->id, 'cantidad' => 3, 'precio_unitario' => (float) $aceite->precio_venta],
            ],
        ]);

        $q2 = $quotes->registrar([
            'tipo' => 'cotizacion',
            'customer_id' => $ramirez->id,
            'fecha' => now()->subDays(2)->toDateString(),
            'fecha_validez' => now()->addDays(10)->toDateString(),
            'items' => [
                ['product_id' => $gaseosa->id, 'cantidad' => 24, 'precio_unitario' => (float) $gaseosa->precio_venta],
                ['product_id' => $fideos->id, 'cantidad' => 20, 'precio_unitario' => (float) $fideos->precio_venta],
            ],
        ]);
        $quotes->cambiarEstado($q2, 'aprobada');

        $quotes->registrar([
            'tipo' => 'pedido',
            'customer_id' => $carlos->id,
            'fecha' => now()->subDay()->toDateString(),
            'items' => [
                ['product_id' => $leche->id, 'cantidad' => 6, 'precio_unitario' => (float) $leche->precio_venta],
            ],
        ]);

        $q4 = $quotes->registrar([
            'tipo' => 'pedido',
            'fecha' => now()->toDateString(),
            'items' => [
                ['product_id' => $arroz->id, 'cantidad' => 2, 'precio_unitario' => (float) $arroz->precio_venta],
                ['product_id' => $gaseosa->id, 'cantidad' => 2, 'precio_unitario' => (float) $gaseosa->precio_venta],
            ],
        ]);
        $quotes->cambiarEstado($q4, 'anulada');

        $quotes->registrar([
            'tipo' => 'cotizacion',
            'customer_id' => $distribuidoraSur->id ?? $pedro->id,
            'fecha' => now()->toDateString(),
            'fecha_validez' => now()->addDays(15)->toDateString(),
            'items' => [
                ['product_id' => $fideos->id, 'cantidad' => 30, 'precio_unitario' => (float) $fideos->precio_venta],
                ['product_id' => $leche->id, 'cantidad' => 25, 'precio_unitario' => (float) $leche->precio_venta],
            ],
        ]);

        Auth::logout();
    }

    private function seedTransfers(array $users, Warehouse $almacen1, Warehouse $almacen2): void
    {
        Auth::login($users['almacenero']);
        $transfers = app(TransferService::class);
        $company = $users['almacenero']->company;

        $aceite = Product::where('company_id', $company->id)->where('codigo', 'P0002')->first();
        $fideos = Product::where('company_id', $company->id)->where('codigo', 'P0008')->first();
        $gaseosa = Product::where('company_id', $company->id)->where('codigo', 'P0003')->first();
        $detergente = Product::where('company_id', $company->id)->where('codigo', 'P0004')->first();

        $transfers->registrar([
            'origin_warehouse_id' => $almacen1->id,
            'dest_warehouse_id' => $almacen2->id,
            'observacion' => 'Abastecimiento inicial de Almacén Secundario',
            'items' => [['product_id' => $aceite->id, 'cantidad' => 10]],
        ]);

        $transfers->registrar([
            'origin_warehouse_id' => $almacen1->id,
            'dest_warehouse_id' => $almacen2->id,
            'items' => [['product_id' => $fideos->id, 'cantidad' => 15]],
        ]);

        $transfers->registrar([
            'origin_warehouse_id' => $almacen2->id,
            'dest_warehouse_id' => $almacen1->id,
            'observacion' => 'Devolución de excedente',
            'items' => [['product_id' => $aceite->id, 'cantidad' => 4]],
        ]);

        $transfers->registrar([
            'origin_warehouse_id' => $almacen1->id,
            'dest_warehouse_id' => $almacen2->id,
            'items' => [['product_id' => $detergente->id, 'cantidad' => 3]],
        ]);

        $transfers->registrar([
            'origin_warehouse_id' => $almacen2->id,
            'dest_warehouse_id' => $almacen1->id,
            'items' => [['product_id' => $fideos->id, 'cantidad' => 5]],
        ]);

        Auth::logout();
    }

    private function seedLots(array $users, Warehouse $almacen1, Warehouse $almacen2): void
    {
        Auth::login($users['almacenero']);
        $lots = app(LotService::class);
        $company = $users['almacenero']->company;

        $leche = Product::where('company_id', $company->id)->where('codigo', 'P0005')->first();
        $gaseosa = Product::where('company_id', $company->id)->where('codigo', 'P0003')->first();
        $agua = Product::where('company_id', $company->id)->where('codigo', 'P0007')->first();

        $lots->registrar($leche->id, $almacen1->id, 'L-2026-08', now()->addMonths(4)->toDateString(), 50, 9.00);
        $lots->registrar($leche->id, $almacen1->id, 'L-2026-09', now()->addMonths(5)->toDateString(), 40, 9.20);
        $lots->registrar($gaseosa->id, $almacen1->id, 'G-2026-07', now()->addMonths(2)->toDateString(), 30, 14.00);
        $lots->registrar($agua->id, $almacen1->id, 'A-2026-06', now()->addMonths(1)->toDateString(), 60, 0.90);
        $lots->registrar($leche->id, $almacen2->id, 'L-2026-08', now()->addMonths(4)->toDateString(), 20, 9.00);

        Auth::logout();
    }

    /** @param  array<int, Sale>  $sales */
    private function seedNotes(array $users, array $sales): void
    {
        Auth::login($users['cajero']);
        $notes = app(NoteService::class);

        // Devolución parcial de un ítem de la venta #2 (yape).
        $sale2 = $sales[2]->fresh('items');
        $notes->emitir($notes->registrarCreditoParcial($sale2, '07', 'Devolución por ítem', [
            ['sale_item_id' => $sale2->items->first()->id, 'cantidad' => 1],
        ]));

        // Anulación completa de la venta #4 (tarjeta).
        $notes->emitir($notes->registrarCredito($sales[4]->fresh(), '01', 'Anulación de la operación'));

        // Notas de débito por intereses/ajustes sobre ventas al crédito/factura.
        $notes->emitir($notes->registrarDebito($sales[3]->fresh(), '01', 'Intereses por mora', 'Interés por mora en el pago', 15));
        $notes->emitir($notes->registrarDebito($sales[1]->fresh(), '02', 'Aumento en el valor', 'Ajuste de precio no facturado', 8));

        // Devolución parcial adicional sobre la venta #3 (factura).
        $sale3 = $sales[3]->fresh('items');
        $notes->emitir($notes->registrarCreditoParcial($sale3, '09', 'Disminución en el valor', [
            ['sale_item_id' => $sale3->items->last()->id, 'cantidad' => 2],
        ]));

        Auth::logout();
    }

    /** @param  array<int, Sale>  $sales */
    private function seedDespatches(Company $company, array $users, array $sales, ?Branch $mainBranch, ?Branch $norteBranch): void
    {
        Auth::login($users['almacenero']);
        $despatches = app(DespatchService::class);

        $base = [
            'fecha_emision' => now()->toDateString(),
            'mod_traslado' => '02',
            'und_peso' => 'KGM',
            'num_bultos' => 1,
            'vehiculo_placa' => 'ABC-123',
            'chofer_tipo_doc' => '1',
            'chofer_num_doc' => '45678912',
            'chofer_licencia' => 'Q12345678',
            'chofer_nombres' => 'José',
            'chofer_apellidos' => 'Ramos Díaz',
        ];

        $despatches->registrar(array_merge($base, [
            'sale_id' => $sales[1]->id,
            'fecha_traslado' => now()->addDay()->toDateString(),
            'cod_traslado' => '01', 'des_traslado' => 'Venta',
            'peso_total' => 8.5,
            'dest_tipo_doc' => '0', 'dest_num_doc' => null, 'dest_nombre' => 'Consumidor final',
            'partida_direccion' => $company->direccion,
            'llegada_direccion' => 'Av. Los Olivos 234, Lima',
            'items' => [['product_id' => $sales[1]->items->first()->product_id, 'descripcion' => $sales[1]->items->first()->descripcion, 'cantidad' => $sales[1]->items->first()->cantidad]],
        ]));

        $despatches->registrar(array_merge($base, [
            'sale_id' => $sales[3]->id,
            'fecha_traslado' => now()->addDay()->toDateString(),
            'cod_traslado' => '01', 'des_traslado' => 'Venta',
            'peso_total' => 25.0, 'num_bultos' => 3,
            'dest_tipo_doc' => '6', 'dest_num_doc' => '20512345678', 'dest_nombre' => 'COMERCIAL RAMÍREZ S.A.C.',
            'partida_direccion' => $company->direccion,
            'llegada_direccion' => 'Av. Industrial 456, Lima',
            'items' => [['product_id' => $sales[3]->items->first()->product_id, 'descripcion' => $sales[3]->items->first()->descripcion, 'cantidad' => $sales[3]->items->first()->cantidad]],
        ]));

        $despatches->registrar(array_merge($base, [
            'fecha_traslado' => now()->addDays(2)->toDateString(),
            'cod_traslado' => '04', 'des_traslado' => 'Traslado entre establecimientos de la misma empresa',
            'peso_total' => 15.0,
            'dest_tipo_doc' => '6', 'dest_num_doc' => $company->ruc, 'dest_nombre' => $company->razon_social,
            'partida_direccion' => $mainBranch?->direccion ?? $company->direccion,
            'llegada_direccion' => $norteBranch?->direccion ?? 'Av. Túpac Amaru 456, Comas',
            'items' => [['product_id' => Product::where('company_id', $company->id)->where('codigo', 'P0004')->first()?->id, 'descripcion' => 'Detergente 2kg', 'cantidad' => 3]],
        ]));

        $despatches->registrar(array_merge($base, [
            'fecha_traslado' => now()->addDays(2)->toDateString(),
            'cod_traslado' => '02', 'des_traslado' => 'Compra',
            'peso_total' => 40.0, 'num_bultos' => 2,
            'dest_tipo_doc' => '6', 'dest_num_doc' => $company->ruc, 'dest_nombre' => $company->razon_social,
            'partida_direccion' => 'Av. Argentina 2300, Cercado de Lima',
            'llegada_direccion' => $company->direccion,
            'items' => [['product_id' => Product::where('company_id', $company->id)->where('codigo', 'P0001')->first()?->id, 'descripcion' => 'Arroz Superior 5kg', 'cantidad' => 50]],
        ]));

        $despatches->registrar(array_merge($base, [
            'sale_id' => $sales[5]->id,
            'fecha_traslado' => now()->addDays(3)->toDateString(),
            'cod_traslado' => '01', 'des_traslado' => 'Venta',
            'peso_total' => 30.0, 'num_bultos' => 2,
            'dest_tipo_doc' => '6', 'dest_num_doc' => '20487654321', 'dest_nombre' => 'DISTRIBUIDORA SUR E.I.R.L.',
            'partida_direccion' => $company->direccion,
            'llegada_direccion' => 'Jr. Comercio 789, Arequipa',
            'items' => [['product_id' => $sales[5]->items->first()->product_id, 'descripcion' => $sales[5]->items->first()->descripcion, 'cantidad' => $sales[5]->items->first()->cantidad]],
        ]));

        Auth::logout();
    }

    private function seedBoletaSummaries(Company $company, User $admin): void
    {
        for ($i = 5; $i >= 1; $i--) {
            $dia = now()->subDays($i);
            BoletaSummary::firstOrCreate(
                ['company_id' => $company->id, 'identificador' => 'RC-' . $dia->format('Ymd') . '-1'],
                [
                    'company_id' => $company->id,
                    'user_id' => $admin->id,
                    'fecha_referencia' => $dia->toDateString(),
                    'fecha_envio' => $dia->copy()->addDay()->toDateString(),
                    'correlativo' => 6 - $i,
                    'ticket' => 'TCK-' . $dia->format('YmdHis'),
                    'estado' => $i === 1 ? 'pendiente' : 'aceptado',
                    'total_boletas' => random_int(1, 4),
                    'mensaje' => $i === 1 ? null : 'La(s) Boleta(s) fueron aceptadas',
                    'sent_at' => $i === 1 ? null : $dia->copy()->addDay(),
                ],
            );
        }
    }

    /** @param  array<int, Sale>  $sales */
    private function seedVoidedDocuments(Company $company, User $admin, array $sales): void
    {
        $datos = [
            ['sale' => $sales[3] ?? null, 'motivo' => 'Anulación por error en el RUC del adquiriente', 'estado' => 'aceptado'],
            ['sale' => $sales[5] ?? null, 'motivo' => 'Corrección por error en la descripción', 'estado' => 'enviado'],
            ['sale' => null, 'motivo' => 'Comprobante emitido por error de digitación', 'estado' => 'pendiente'],
        ];

        foreach ($datos as $i => $d) {
            $dia = now()->subDays($i + 1);
            VoidedDocument::firstOrCreate(
                ['company_id' => $company->id, 'identificador' => 'RA-' . $dia->format('Ymd') . '-' . ($i + 1)],
                [
                    'company_id' => $company->id,
                    'user_id' => $admin->id,
                    'sale_id' => $d['sale']?->id,
                    'fecha_referencia' => $dia->toDateString(),
                    'fecha_comunicacion' => $dia->toDateString(),
                    'correlativo' => $i + 1,
                    'motivo' => $d['motivo'],
                    'estado' => $d['estado'],
                ],
            );
        }
    }
}

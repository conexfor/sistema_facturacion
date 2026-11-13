import Card from '@/Components/Card';
import Badge from '@/Components/ui/Badge';
import Button from '@/Components/ui/Button';
import { SelectField, Switch, TextField } from '@/Components/ui/Form';
import PageHeader from '@/Components/ui/PageHeader';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router, useForm } from '@inertiajs/react';
import { BadgeCheck, CheckCircle2, ShieldAlert, UploadCloud } from 'lucide-react';
import { FormEvent, useState } from 'react';

interface CertificadoInfo {
    ruc: string | null;
    titular: string | null;
    emisor: string | null;
    vence: string | null;
    vencido: boolean;
    coincide: boolean;
}

const formatoFecha = (iso: string) => iso.split('-').reverse().join('/');

interface CompanySunat {
    ruc: string;
    razon_social: string;
    ubigeo: string | null;
    departamento: string | null;
    provincia: string | null;
    distrito: string | null;
    direccion: string | null;
    sunat_modo: string;
    agente_percepcion: boolean;
    percepcion_tasa: number;
    sol_user: string | null;
    tiene_certificado: boolean;
    tiene_clave_sol: boolean;
    gre_client_id: string | null;
    tiene_gre_secret: boolean;
}

export default function Edit({
    company,
    configurado,
    certificado,
}: {
    company: CompanySunat;
    configurado: boolean;
    certificado: CertificadoInfo | null;
}) {
    const [aplicando, setAplicando] = useState(false);

    const aplicarCertificado = () =>
        router.post(
            route('settings.sunat.aplicar-certificado'),
            {},
            {
                // Remontar la página para que el formulario tome el nuevo domicilio.
                preserveState: false,
                onStart: () => setAplicando(true),
                onFinish: () => setAplicando(false),
            },
        );

    const form = useForm<{
        ubigeo: string;
        departamento: string;
        provincia: string;
        distrito: string;
        direccion: string;
        sunat_modo: string;
        agente_percepcion: boolean;
        percepcion_tasa: string;
        sol_user: string;
        sol_pass: string;
        certificado: File | null;
        certificado_password: string;
        gre_client_id: string;
        gre_client_secret: string;
    }>({
        ubigeo: company.ubigeo ?? '',
        departamento: company.departamento ?? '',
        provincia: company.provincia ?? '',
        distrito: company.distrito ?? '',
        direccion: company.direccion ?? '',
        sunat_modo: company.sunat_modo ?? 'beta',
        agente_percepcion: company.agente_percepcion ?? false,
        percepcion_tasa: String(company.percepcion_tasa ?? 2),
        sol_user: company.sol_user ?? '',
        sol_pass: '',
        certificado: null,
        certificado_password: '',
        gre_client_id: company.gre_client_id ?? '',
        gre_client_secret: '',
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        // Si se subió un certificado, el servidor actualiza los datos de la
        // empresa: se remonta la página para que el formulario los muestre
        // (salvo que haya errores, para no perder lo escrito).
        form.post(route('settings.sunat.update'), {
            preserveScroll: true,
            preserveState: 'errors',
        });
    };

    return (
        <AuthenticatedLayout>
            <Head title="Configuración SUNAT" />
            <form onSubmit={submit} className="space-y-5">
                <PageHeader
                    title="Configuración SUNAT"
                    description="Datos fiscales y credenciales para facturación electrónica"
                    breadcrumbs={[{ label: 'Configuración' }, { label: 'SUNAT' }]}
                    actions={
                        <Button type="submit" disabled={form.processing}>
                            Guardar
                        </Button>
                    }
                />

                <div className="grid grid-cols-1 gap-4 lg:grid-cols-3">
                    <div className="space-y-4 lg:col-span-2">
                        <Card title="Domicilio fiscal" subtitle="Aparece en el comprobante electrónico">
                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <TextField
                                    label="Ubigeo"
                                    value={form.data.ubigeo}
                                    onChange={(e) => form.setData('ubigeo', e.target.value)}
                                    error={form.errors.ubigeo}
                                    hint="6 dígitos (ej. 150101)"
                                />
                                <TextField
                                    label="Departamento"
                                    value={form.data.departamento}
                                    onChange={(e) => form.setData('departamento', e.target.value)}
                                />
                                <TextField
                                    label="Provincia"
                                    value={form.data.provincia}
                                    onChange={(e) => form.setData('provincia', e.target.value)}
                                />
                                <TextField
                                    label="Distrito"
                                    value={form.data.distrito}
                                    onChange={(e) => form.setData('distrito', e.target.value)}
                                />
                                <div className="sm:col-span-2">
                                    <TextField
                                        label="Dirección"
                                        value={form.data.direccion}
                                        onChange={(e) => form.setData('direccion', e.target.value)}
                                    />
                                </div>
                            </div>
                        </Card>

                        <Card title="Percepción del IGV" subtitle="Solo si SUNAT te designó Agente de Percepción">
                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <Switch
                                    label="Agente de percepción"
                                    description="Activa el cobro de percepción en las ventas"
                                    checked={form.data.agente_percepcion}
                                    onChange={(v) => form.setData('agente_percepcion', v)}
                                />
                                {form.data.agente_percepcion && (
                                    <TextField
                                        label="Tasa de percepción (%)"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        max="100"
                                        value={form.data.percepcion_tasa}
                                        onChange={(e) => form.setData('percepcion_tasa', e.target.value)}
                                        error={form.errors.percepcion_tasa}
                                        hint="Régimen general: 2%"
                                    />
                                )}
                            </div>
                        </Card>

                        <Card title="Credenciales SUNAT (SOL)">
                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <SelectField
                                    label="Modo"
                                    value={form.data.sunat_modo}
                                    onChange={(e) => form.setData('sunat_modo', e.target.value)}
                                    options={[
                                        { value: 'beta', label: 'Beta (homologación)' },
                                        { value: 'produccion', label: 'Producción' },
                                    ]}
                                />
                                <TextField
                                    label="Usuario SOL"
                                    value={form.data.sol_user}
                                    onChange={(e) => form.setData('sol_user', e.target.value)}
                                    error={form.errors.sol_user}
                                    hint="Usuario secundario SOL"
                                />
                                <TextField
                                    label="Clave SOL"
                                    type="password"
                                    value={form.data.sol_pass}
                                    onChange={(e) => form.setData('sol_pass', e.target.value)}
                                    error={form.errors.sol_pass}
                                    placeholder={company.tiene_clave_sol ? '•••••• (sin cambios)' : ''}
                                    hint={
                                        company.tiene_clave_sol
                                            ? '✓ Guardada (cifrada). Por seguridad no se muestra; escribe una nueva solo para cambiarla.'
                                            : undefined
                                    }
                                />
                            </div>

                            <div className="mt-4">
                                <label className="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">
                                    Certificado digital (.pfx o .pem)
                                </label>
                                {company.tiene_certificado && (
                                    <p className="mb-2 rounded-lg bg-emerald-50 px-3 py-2 text-xs text-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-300">
                                        ✓ Certificado guardado
                                        {certificado?.titular ? ` de ${certificado.titular}` : ''}
                                        {certificado?.vence ? ` · vence ${formatoFecha(certificado.vence)}` : ''}.
                                        Sube otro archivo solo si quieres reemplazarlo.
                                    </p>
                                )}
                                <div className="flex items-center gap-3 rounded-lg border border-dashed border-slate-300 px-4 py-3 dark:border-slate-700">
                                    <UploadCloud className="size-5 text-slate-400" />
                                    <input
                                        type="file"
                                        accept=".pfx,.p12,.pem,.txt,.crt,.key"
                                        onChange={(e) =>
                                            form.setData('certificado', e.target.files?.[0] ?? null)
                                        }
                                        className="text-sm text-slate-600 dark:text-slate-300"
                                    />
                                </div>
                                {form.errors.certificado && (
                                    <p className="mt-1 text-xs text-rose-600">{form.errors.certificado}</p>
                                )}
                                <div className="mt-3">
                                    <TextField
                                        label="Contraseña del certificado (.pfx)"
                                        type="password"
                                        value={form.data.certificado_password}
                                        onChange={(e) => form.setData('certificado_password', e.target.value)}
                                        hint="Requerida solo si subes un .pfx/.p12"
                                    />
                                </div>
                                <p className="mt-1 text-xs text-slate-400">
                                    Aceptamos .pfx/.p12 (lo convertimos a PEM con su clave) o .pem directamente.
                                </p>
                            </div>
                        </Card>

                        <Card
                            title="Guías de remisión electrónica (GRE)"
                            subtitle="Credenciales API que generas en SUNAT SOL (menú Cliente API GRE). Solo para producción."
                        >
                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <TextField
                                    label="GRE Client ID"
                                    value={form.data.gre_client_id}
                                    onChange={(e) => form.setData('gre_client_id', e.target.value)}
                                    error={form.errors.gre_client_id}
                                    hint="client_id de la API GRE"
                                />
                                <TextField
                                    label="GRE Client Secret"
                                    type="password"
                                    value={form.data.gre_client_secret}
                                    onChange={(e) => form.setData('gre_client_secret', e.target.value)}
                                    error={form.errors.gre_client_secret}
                                    placeholder={company.tiene_gre_secret ? '•••••• (sin cambios)' : ''}
                                    hint={
                                        company.tiene_gre_secret
                                            ? '✓ Guardado (cifrado). Escribe uno nuevo solo para cambiarlo.'
                                            : undefined
                                    }
                                />
                            </div>
                            <p className="mt-2 text-xs text-slate-400">
                                En modo <strong>Beta</strong> las guías se envían al simulador de homologación GRE
                                (no necesitas estas credenciales). En <strong>Producción</strong> son obligatorias.
                            </p>
                        </Card>
                    </div>

                    <div className="space-y-4">
                        <Card title="Estado">
                            <div
                                className={`flex items-center gap-3 rounded-xl px-4 py-3 ${
                                    configurado
                                        ? 'bg-emerald-50 dark:bg-emerald-900/20'
                                        : 'bg-amber-50 dark:bg-amber-900/20'
                                }`}
                            >
                                {configurado ? (
                                    <CheckCircle2 className="size-6 text-emerald-500" />
                                ) : (
                                    <ShieldAlert className="size-6 text-amber-500" />
                                )}
                                <div>
                                    <p className="text-sm font-semibold text-slate-800 dark:text-slate-100">
                                        {configurado ? 'Listo para emitir' : 'Modo simulación'}
                                    </p>
                                    <p className="text-xs text-slate-500 dark:text-slate-400">
                                        {configurado
                                            ? 'Los comprobantes se enviarán a SUNAT.'
                                            : 'Faltan credenciales; se generan en simulación.'}
                                    </p>
                                </div>
                            </div>

                            <ul className="mt-4 space-y-2 text-sm">
                                <li className="flex items-center justify-between">
                                    <span className="text-slate-500 dark:text-slate-400">Certificado</span>
                                    {company.tiene_certificado ? (
                                        <Badge tone="emerald">Cargado</Badge>
                                    ) : (
                                        <Badge tone="slate">Pendiente</Badge>
                                    )}
                                </li>
                                <li className="flex items-center justify-between">
                                    <span className="text-slate-500 dark:text-slate-400">Clave SOL</span>
                                    {company.tiene_clave_sol ? (
                                        <Badge tone="emerald">Configurada</Badge>
                                    ) : (
                                        <Badge tone="slate">Pendiente</Badge>
                                    )}
                                </li>
                                <li className="flex items-center justify-between">
                                    <span className="text-slate-500 dark:text-slate-400">RUC</span>
                                    <span className="font-mono text-xs">{company.ruc}</span>
                                </li>
                            </ul>
                        </Card>

                        {certificado && (
                            <Card title="Certificado digital">
                                <ul className="space-y-2 text-sm">
                                    <li>
                                        <span className="block text-xs text-slate-400">Titular</span>
                                        <span className="font-medium text-slate-800 dark:text-slate-100">
                                            {certificado.titular ?? '—'}
                                        </span>
                                    </li>
                                    <li className="flex items-center justify-between">
                                        <span className="text-slate-500 dark:text-slate-400">RUC del certificado</span>
                                        {certificado.ruc ? (
                                            <span className="flex items-center gap-1 font-mono text-xs">
                                                {certificado.coincide && (
                                                    <BadgeCheck className="size-4 text-emerald-500" />
                                                )}
                                                {certificado.ruc}
                                            </span>
                                        ) : (
                                            <Badge tone="slate">No detectado</Badge>
                                        )}
                                    </li>
                                    {certificado.vence && (
                                        <li className="flex items-center justify-between">
                                            <span className="text-slate-500 dark:text-slate-400">Vence</span>
                                            <Badge tone={certificado.vencido ? 'rose' : 'emerald'}>
                                                {certificado.vencido ? 'Vencido ' : ''}
                                                {formatoFecha(certificado.vence)}
                                            </Badge>
                                        </li>
                                    )}
                                    {certificado.emisor && (
                                        <li>
                                            <span className="block text-xs text-slate-400">Emitido por</span>
                                            <span className="text-xs text-slate-600 dark:text-slate-300">
                                                {certificado.emisor}
                                            </span>
                                        </li>
                                    )}
                                </ul>

                                {certificado.ruc && !certificado.coincide && (
                                    <div className="mt-3 rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-700 dark:bg-amber-900/20 dark:text-amber-300">
                                        <p>
                                            El RUC del certificado no coincide con el de la empresa ({company.ruc}).
                                            Al usar sus datos se reemplazan RUC, razón social y domicilio fiscal, y
                                            se borran teléfono y correo de la empresa anterior.
                                        </p>
                                        <Button
                                            type="button"
                                            size="sm"
                                            variant="secondary"
                                            className="mt-2"
                                            onClick={aplicarCertificado}
                                            disabled={aplicando}
                                        >
                                            Usar datos del certificado
                                        </Button>
                                    </div>
                                )}
                            </Card>
                        )}
                    </div>
                </div>
            </form>
        </AuthenticatedLayout>
    );
}

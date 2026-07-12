import Card from '@/Components/Card';
import Badge from '@/Components/ui/Badge';
import Button from '@/Components/ui/Button';
import { SelectField, Switch, TextField } from '@/Components/ui/Form';
import PageHeader from '@/Components/ui/PageHeader';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm } from '@inertiajs/react';
import { CheckCircle2, ShieldAlert, UploadCloud } from 'lucide-react';
import { FormEvent } from 'react';

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
}: {
    company: CompanySunat;
    configurado: boolean;
}) {
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
        form.post(route('settings.sunat.update'), { preserveScroll: true });
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
                                />
                            </div>

                            <div className="mt-4">
                                <label className="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">
                                    Certificado digital (.pfx o .pem)
                                </label>
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
                    </div>
                </div>
            </form>
        </AuthenticatedLayout>
    );
}

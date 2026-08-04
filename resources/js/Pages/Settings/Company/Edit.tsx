import Card from '@/Components/Card';
import Button from '@/Components/ui/Button';
import { SelectField, TextField } from '@/Components/ui/Form';
import PageHeader from '@/Components/ui/PageHeader';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm } from '@inertiajs/react';
import { Building2, UploadCloud } from 'lucide-react';
import { FormEvent } from 'react';

interface CompanyData {
    ruc: string;
    razon_social: string;
    nombre_comercial: string | null;
    telefono: string | null;
    email: string | null;
    moneda: string;
    igv_porcentaje: number;
    logo_path: string | null;
}

export default function Edit({ company }: { company: CompanyData }) {
    const form = useForm<{
        ruc: string;
        razon_social: string;
        nombre_comercial: string;
        telefono: string;
        email: string;
        moneda: string;
        igv_porcentaje: string;
        logo: File | null;
    }>({
        ruc: company.ruc ?? '',
        razon_social: company.razon_social ?? '',
        nombre_comercial: company.nombre_comercial ?? '',
        telefono: company.telefono ?? '',
        email: company.email ?? '',
        moneda: company.moneda ?? 'PEN',
        igv_porcentaje: String(company.igv_porcentaje ?? 18),
        logo: null,
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.post(route('settings.empresa.update'), { preserveScroll: true });
    };

    return (
        <AuthenticatedLayout>
            <Head title="Datos de la empresa" />
            <form onSubmit={submit} className="space-y-5">
                <PageHeader
                    title="Datos de la empresa"
                    description="RUC, razón social y datos generales que aparecen en tus comprobantes"
                    breadcrumbs={[{ label: 'Configuración' }, { label: 'Empresa' }]}
                    actions={
                        <Button type="submit" disabled={form.processing}>
                            Guardar
                        </Button>
                    }
                />

                <div className="grid grid-cols-1 gap-4 lg:grid-cols-3">
                    <div className="space-y-4 lg:col-span-2">
                        <Card
                            title="Identificación"
                            subtitle="El RUC debe coincidir con el del certificado digital y las credenciales SOL"
                        >
                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <TextField
                                    label="RUC"
                                    value={form.data.ruc}
                                    onChange={(e) => form.setData('ruc', e.target.value)}
                                    error={form.errors.ruc}
                                    hint="11 dígitos"
                                    maxLength={11}
                                />
                                <TextField
                                    label="Razón social"
                                    value={form.data.razon_social}
                                    onChange={(e) => form.setData('razon_social', e.target.value)}
                                    error={form.errors.razon_social}
                                />
                                <div className="sm:col-span-2">
                                    <TextField
                                        label="Nombre comercial"
                                        value={form.data.nombre_comercial}
                                        onChange={(e) => form.setData('nombre_comercial', e.target.value)}
                                        error={form.errors.nombre_comercial}
                                        hint="Opcional; se usa en el comprobante si lo completas"
                                    />
                                </div>
                            </div>
                        </Card>

                        <Card title="Contacto">
                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <TextField
                                    label="Teléfono"
                                    value={form.data.telefono}
                                    onChange={(e) => form.setData('telefono', e.target.value)}
                                    error={form.errors.telefono}
                                />
                                <TextField
                                    label="Email"
                                    type="email"
                                    value={form.data.email}
                                    onChange={(e) => form.setData('email', e.target.value)}
                                    error={form.errors.email}
                                />
                            </div>
                        </Card>

                        <Card title="Facturación">
                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <SelectField
                                    label="Moneda"
                                    value={form.data.moneda}
                                    onChange={(e) => form.setData('moneda', e.target.value)}
                                    options={[
                                        { value: 'PEN', label: 'Soles (PEN)' },
                                        { value: 'USD', label: 'Dólares (USD)' },
                                    ]}
                                />
                                <TextField
                                    label="IGV (%)"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    max="100"
                                    value={form.data.igv_porcentaje}
                                    onChange={(e) => form.setData('igv_porcentaje', e.target.value)}
                                    error={form.errors.igv_porcentaje}
                                />
                            </div>

                            <div className="mt-4">
                                <label className="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">
                                    Logo (aparece en el PDF del comprobante)
                                </label>
                                <div className="flex items-center gap-3 rounded-lg border border-dashed border-slate-300 px-4 py-3 dark:border-slate-700">
                                    <UploadCloud className="size-5 text-slate-400" />
                                    <input
                                        type="file"
                                        accept="image/*"
                                        onChange={(e) => form.setData('logo', e.target.files?.[0] ?? null)}
                                        className="text-sm text-slate-600 dark:text-slate-300"
                                    />
                                </div>
                                {form.errors.logo && (
                                    <p className="mt-1 text-xs text-rose-600">{form.errors.logo}</p>
                                )}
                            </div>
                        </Card>
                    </div>

                    <div className="space-y-4">
                        <Card title="Vista previa">
                            <div className="flex items-center gap-3 rounded-xl bg-slate-50 px-4 py-3 dark:bg-slate-800/50">
                                <span className="flex size-11 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-900/30 dark:text-brand-300">
                                    <Building2 className="size-5" />
                                </span>
                                <div className="min-w-0">
                                    <p className="truncate text-sm font-semibold text-slate-800 dark:text-slate-100">
                                        {form.data.razon_social || 'Razón social'}
                                    </p>
                                    <p className="font-mono text-xs text-slate-500 dark:text-slate-400">
                                        RUC {form.data.ruc || '—'}
                                    </p>
                                </div>
                            </div>
                            <p className="mt-3 text-xs text-slate-400">
                                Después de cambiar el RUC, revisa que el certificado digital y las
                                credenciales SOL en Configuración SUNAT correspondan a este mismo RUC.
                            </p>
                        </Card>
                    </div>
                </div>
            </form>
        </AuthenticatedLayout>
    );
}

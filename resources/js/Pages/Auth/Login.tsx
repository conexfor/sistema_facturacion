import InputError from '@/Components/InputError';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { Eye, EyeOff, Lock, Mail } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

const labelClass =
    'block text-[11px] font-semibold uppercase tracking-[0.12em] text-ff-muted';

const inputClass =
    'block w-full rounded-lg border border-white/[0.08] bg-white/[0.04] py-2.5 ps-10 text-sm text-ff-text placeholder:text-ff-dim transition duration-200 focus:border-[#00d4aa]/50 focus:bg-white/[0.07] focus:outline-none focus:ring-2 focus:ring-[#00d4aa]/15';

export default function Login({
    status,
    canResetPassword,
}: {
    status?: string;
    canResetPassword: boolean;
}) {
    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
        remember: false as boolean,
    });

    const [showPassword, setShowPassword] = useState(false);

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        post(route('login'), {
            onFinish: () => reset('password'),
        });
    };

    return (
        <GuestLayout>
            <Head title="Iniciar sesión" />

            <div className="mb-6">
                <h1 className="font-display text-2xl font-semibold tracking-tight text-ff-text">
                    Bienvenido de nuevo
                </h1>
                <p className="mt-1 text-sm text-ff-muted">
                    Ingresa tus credenciales para acceder al sistema.
                </p>
            </div>

            {status && (
                <div className="mb-4 rounded-lg border border-[#00d4aa]/25 bg-[#00d4aa]/10 px-3 py-2 text-sm font-medium text-ff-cyan">
                    {status}
                </div>
            )}

            <form onSubmit={submit} className="space-y-5">
                <div>
                    <label htmlFor="email" className={labelClass}>
                        Correo electrónico
                    </label>
                    <div className="relative mt-2">
                        <Mail className="pointer-events-none absolute inset-y-0 start-3 my-auto size-4 text-ff-dim" />
                        <input
                            id="email"
                            type="email"
                            name="email"
                            value={data.email}
                            className={`${inputClass} pe-3`}
                            placeholder="usuario@empresa.com"
                            autoComplete="username"
                            autoFocus
                            onChange={(e) => setData('email', e.target.value)}
                        />
                    </div>
                    <InputError message={errors.email} className="mt-2" />
                </div>

                <div>
                    <div className="flex items-center justify-between">
                        <label htmlFor="password" className={labelClass}>
                            Contraseña
                        </label>
                        {canResetPassword && (
                            <Link
                                href={route('password.request')}
                                className="text-xs font-medium text-ff-cyan transition duration-200 hover:text-[#3de8c4] focus:outline-none focus-visible:underline"
                            >
                                ¿Olvidaste tu contraseña?
                            </Link>
                        )}
                    </div>
                    <div className="relative mt-2">
                        <Lock className="pointer-events-none absolute inset-y-0 start-3 my-auto size-4 text-ff-dim" />
                        <input
                            id="password"
                            type={showPassword ? 'text' : 'password'}
                            name="password"
                            value={data.password}
                            className={`${inputClass} pe-10`}
                            placeholder="••••••••"
                            autoComplete="current-password"
                            onChange={(e) => setData('password', e.target.value)}
                        />
                        <button
                            type="button"
                            onClick={() => setShowPassword((v) => !v)}
                            className="absolute inset-y-0 end-0 flex items-center pe-3 text-ff-dim transition duration-200 hover:text-ff-muted focus:outline-none"
                            aria-label={
                                showPassword
                                    ? 'Ocultar contraseña'
                                    : 'Mostrar contraseña'
                            }
                            title={
                                showPassword
                                    ? 'Ocultar contraseña'
                                    : 'Mostrar contraseña'
                            }
                            tabIndex={-1}
                        >
                            {showPassword ? (
                                <EyeOff className="size-4" />
                            ) : (
                                <Eye className="size-4" />
                            )}
                        </button>
                    </div>
                    <InputError message={errors.password} className="mt-2" />
                </div>

                <label className="flex cursor-pointer select-none items-center gap-2.5">
                    <input
                        type="checkbox"
                        name="remember"
                        checked={data.remember}
                        onChange={(e) =>
                            setData('remember', (e.target.checked || false) as false)
                        }
                        className="size-4 rounded border-white/15 bg-white/[0.04] text-[#00d4aa] focus:ring-[#00d4aa]/30 focus:ring-offset-0"
                    />
                    <span className="text-sm text-ff-muted">
                        Mantener sesión iniciada
                    </span>
                </label>

                <button
                    type="submit"
                    disabled={processing}
                    className="ff-btn-primary flex w-full items-center justify-center gap-2 rounded-lg px-4 py-3 font-display text-sm font-semibold tracking-wide transition duration-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#00d4aa]/60 focus-visible:ring-offset-2 focus-visible:ring-offset-[#0d1426] disabled:cursor-wait disabled:opacity-80"
                >
                    {processing && (
                        <svg
                            className="size-4 animate-spin"
                            viewBox="0 0 24 24"
                            fill="none"
                            aria-hidden
                        >
                            <circle
                                cx="12"
                                cy="12"
                                r="10"
                                stroke="currentColor"
                                strokeOpacity="0.25"
                                strokeWidth="3"
                            />
                            <path
                                d="M22 12a10 10 0 0 0-10-10"
                                stroke="currentColor"
                                strokeWidth="3"
                                strokeLinecap="round"
                            />
                        </svg>
                    )}
                    {processing ? 'Ingresando…' : 'Iniciar sesión'}
                </button>
            </form>
        </GuestLayout>
    );
}

import BrandLogo from '@/Components/BrandLogo';
import { Link } from '@inertiajs/react';
import { PropsWithChildren } from 'react';
import loginBg from '../../images/login.png';

/**
 * Layout de las pantallas de autenticación: fondo fotográfico con doble
 * overlay + cuadrícula fina, y tarjeta glassmorphism centrada. Siempre se
 * muestra en oscuro (fuerza la clase `dark` en su subárbol).
 */
export default function Guest({ children }: PropsWithChildren) {
    return (
        <div className="dark relative flex min-h-screen items-center justify-center overflow-hidden bg-ff-base px-4 py-10 font-sans text-ff-text">
            {/* Fotografía de fondo */}
            <div
                aria-hidden
                className="absolute inset-0 bg-cover bg-center"
                style={{ backgroundImage: `url(${loginBg})` }}
            />
            {/* Overlay oscuro (78%) */}
            <div aria-hidden className="absolute inset-0 bg-[#070b14]/[0.78]" />
            {/* Overlay radial cyan desde abajo */}
            <div
                aria-hidden
                className="absolute inset-0"
                style={{
                    background:
                        'radial-gradient(70rem 32rem at 50% 115%, rgb(0 212 170 / 0.16), transparent 65%)',
                }}
            />
            {/* Cuadrícula fina 48×48 al 3% */}
            <div
                aria-hidden
                className="absolute inset-0 opacity-[0.03]"
                style={{
                    backgroundImage:
                        'linear-gradient(#fff 1px, transparent 1px), linear-gradient(90deg, #fff 1px, transparent 1px)',
                    backgroundSize: '48px 48px',
                }}
            />

            <div className="relative w-full max-w-[420px]">
                <div className="rounded-2xl border border-white/[0.09] bg-[rgba(13,20,38,0.72)] p-8 shadow-2xl shadow-black/40 backdrop-blur-[24px]">
                    <Link href="/" className="mb-8 inline-flex">
                        <BrandLogo size="lg" subtitle="Facturación Electrónica" />
                    </Link>

                    {children}
                </div>

                <p className="mt-6 text-center font-mono text-[11px] text-ff-dim">
                    © {new Date().getFullYear()} FactuFlow · Facturación Electrónica
                </p>
            </div>
        </div>
    );
}

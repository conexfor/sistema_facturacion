import { PasswordToggle } from '@/Components/ui/Form';
import {
    forwardRef,
    InputHTMLAttributes,
    useEffect,
    useImperativeHandle,
    useRef,
    useState,
} from 'react';

const baseClass =
    'rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:focus:border-indigo-600 dark:focus:ring-indigo-600 ';

export default forwardRef(function TextInput(
    {
        type = 'text',
        className = '',
        isFocused = false,
        ...props
    }: InputHTMLAttributes<HTMLInputElement> & { isFocused?: boolean },
    ref,
) {
    const localRef = useRef<HTMLInputElement>(null);
    const [visible, setVisible] = useState(false);

    useImperativeHandle(ref, () => ({
        focus: () => localRef.current?.focus(),
    }));

    useEffect(() => {
        if (isFocused) {
            localRef.current?.focus();
        }
    }, [isFocused]);

    if (type === 'password') {
        // Las clases de layout (margen, ancho) van al contenedor para que el
        // botón del ojo quede alineado dentro del input.
        return (
            <div className={'relative ' + className}>
                <input
                    {...props}
                    type={visible ? 'text' : 'password'}
                    className={baseClass + 'block w-full pe-10'}
                    ref={localRef}
                />
                <PasswordToggle visible={visible} onToggle={() => setVisible((v) => !v)} />
            </div>
        );
    }

    return (
        <input
            {...props}
            type={type}
            className={baseClass + className}
            ref={localRef}
        />
    );
});

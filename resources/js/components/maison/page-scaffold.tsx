/**
 * Temporary stand-in for a page whose content has not been built yet, so every
 * route resolves and is navigable while the pages land one at a time. Each page
 * drops this as it is implemented, and the component goes with the last one.
 */
export function PageScaffold({ title }: { title: string }) {
    return (
        <section className="flex min-h-[70vh] flex-col items-center justify-center px-6 text-center">
            <p className="font-sans text-[11px] font-light tracking-[0.3em] text-gold uppercase">
                Maison Anversa
            </p>

            <span
                aria-hidden="true"
                className="my-6 block h-px w-16 bg-gold/40"
            />

            <h1 className="font-serif text-4xl">{title}</h1>

            <p className="mt-4 max-w-md font-sans text-sm font-light text-stone">
                This page is still being built.
            </p>
        </section>
    );
}

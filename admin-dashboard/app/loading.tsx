export default function Loading() {
  return (
    <main className="min-h-screen bg-white px-6 py-10">
      <div className="mx-auto max-w-6xl space-y-6">
        <div className="h-12 w-48 animate-pulse rounded-full bg-zinc-200" />
        <div className="h-16 max-w-3xl animate-pulse rounded-2xl bg-zinc-200" />
        <div className="grid gap-5 md:grid-cols-3">
          {Array.from({ length: 3 }).map((_, index) => (
            <div key={index} className="h-52 animate-pulse rounded-2xl bg-zinc-100" />
          ))}
        </div>
      </div>
    </main>
  );
}

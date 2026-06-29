'use client';

import { useEffect, useState } from 'react';

type TimeLeft = {
  days: number;
  hours: number;
  minutes: number;
  seconds: number;
};

function pad(n: number) {
  return String(n).padStart(2, '0');
}

function getTimeLeft(target: string): TimeLeft | null {
  const diff = new Date(target).getTime() - Date.now();
  if (diff <= 0) return null;
  return {
    days: Math.floor(diff / (1000 * 60 * 60 * 24)),
    hours: Math.floor((diff / (1000 * 60 * 60)) % 24),
    minutes: Math.floor((diff / (1000 * 60)) % 60),
    seconds: Math.floor((diff / 1000) % 60),
  };
}

type Props = {
  targetDate: string;
  accentClass?: string;
};

export function EventCountdown({ targetDate, accentClass = 'from-blue-500 via-indigo-500 to-violet-500' }: Props) {
  const [timeLeft, setTimeLeft] = useState<TimeLeft | null>(null);
  const [mounted, setMounted] = useState(false);

  useEffect(() => {
    setMounted(true);
    setTimeLeft(getTimeLeft(targetDate));
    const id = setInterval(() => setTimeLeft(getTimeLeft(targetDate)), 1000);
    return () => clearInterval(id);
  }, [targetDate]);

  if (!mounted) {
    return <div className="h-20 animate-pulse rounded-2xl bg-zinc-200" />;
  }

  if (!timeLeft) {
    return (
      <div className={`inline-flex items-center gap-2 rounded-full bg-gradient-to-r ${accentClass} px-4 py-1.5 text-sm font-semibold text-white shadow`}>
        <span className="h-2 w-2 animate-pulse rounded-full bg-white" />
        Event is live now
      </div>
    );
  }

  const units = [
    { label: 'Days', value: timeLeft.days },
    { label: 'Hrs', value: timeLeft.hours },
    { label: 'Min', value: timeLeft.minutes },
    { label: 'Sec', value: timeLeft.seconds },
  ];

  return (
    <div className="flex items-center gap-2 sm:gap-3">
      {units.map((unit, i) => (
        <div key={unit.label} className="flex items-center gap-2 sm:gap-3">
          <div className="flex flex-col items-center">
            <div className={`relative flex h-10 w-10 items-center justify-center rounded-lg bg-gradient-to-br ${accentClass} shadow sm:h-11 sm:w-11`}>
              <span className="text-sm font-bold tabular-nums text-white sm:text-base">
                {pad(unit.value)}
              </span>
            </div>
            <span className="mt-1 text-[9px] font-semibold uppercase tracking-widest text-zinc-500">
              {unit.label}
            </span>
          </div>
          {i < units.length - 1 && (
            <span className="mb-4 text-lg font-bold text-zinc-400">:</span>
          )}
        </div>
      ))}
    </div>
  );
}

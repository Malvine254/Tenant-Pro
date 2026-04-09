export const API_BASE_URL =
  process.env.NEXT_PUBLIC_API_BASE_URL ?? 'http://localhost:3000/api';

export async function uploadFile<T>(path: string, token: string, file: File): Promise<T> {
  const formData = new FormData();
  formData.append('file', file);

  let response: Response;
  try {
    response = await fetch(`${API_BASE_URL}${path}`, {
      method: 'POST',
      headers: {
        ...(token ? { Authorization: `Bearer ${token}` } : {}),
      },
      body: formData,
      cache: 'no-store',
    });
  } catch {
    throw new Error(`Unable to reach API at ${API_BASE_URL}. Ensure backend is running on localhost:3000.`);
  }

  if (!response.ok) {
    throw new Error((await response.text()) || `Upload failed with ${response.status}`);
  }

  return response.json() as Promise<T>;
}

export async function apiRequest<T>(
  path: string,
  token?: string,
  init?: RequestInit,
): Promise<T> {
  let response: Response;

  try {
    response = await fetch(`${API_BASE_URL}${path}`, {
      ...init,
      headers: {
        'Content-Type': 'application/json',
        ...(token ? { Authorization: `Bearer ${token}` } : {}),
        ...(init?.headers ?? {}),
      },
      cache: 'no-store',
    });
  } catch {
    throw new Error(
      `Unable to reach API at ${API_BASE_URL}. Ensure backend is running on localhost:3000.`,
    );
  }

  if (!response.ok) {
    const text = await response.text();

    try {
      const parsed = JSON.parse(text) as {
        message?: string | string[];
        error?: string;
      };

      if (Array.isArray(parsed.message)) {
        throw new Error(parsed.message.join(', '));
      }

      if (typeof parsed.message === 'string' && parsed.message.length > 0) {
        throw new Error(parsed.message);
      }

      if (typeof parsed.error === 'string' && parsed.error.length > 0) {
        throw new Error(parsed.error);
      }
    } catch (error) {
      if (error instanceof Error && error.message !== text) {
        throw error;
      }
    }

    throw new Error(text || `Request failed with ${response.status}`);
  }

  return response.json() as Promise<T>;
}

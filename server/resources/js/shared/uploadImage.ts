export async function uploadImage(
  file: File,
  endpoint: string,
): Promise<string> {
  const body = new FormData();
  body.append('file', file);
  const token = decodeURIComponent(
    document.cookie
      .split('; ')
      .find((cookie) => cookie.startsWith('XSRF-TOKEN='))
      ?.slice(11) ?? '',
  );
  const response = await fetch(endpoint, {
    method: 'POST',
    headers: { Accept: 'application/json', 'X-XSRF-TOKEN': token },
    body,
  });
  const data = await response.json();
  if (!response.ok) {
    throw new Error(
      data.errors?.file?.[0] ??
        data.message ??
        'Upload failed. Please try again.',
    );
  }

  return data.id;
}

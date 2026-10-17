const ORIGIN = 'https://api-pulse.nobeladam.cn';

export default async function onRequest(context) {
  const request = context.request;
  const url = new URL(request.url);
  const target = new URL(`${url.pathname}${url.search}`, ORIGIN);
  const headers = new Headers(request.headers);

  headers.set('Host', 'api-pulse.nobeladam.cn');
  headers.set('X-Forwarded-Proto', 'https');
  headers.set('Accept-Encoding', 'identity');
  headers.delete('content-length');

  const body = ['GET', 'HEAD'].includes(request.method)
    ? undefined
    : await request.arrayBuffer();

  const response = await fetch(target, {
    method: request.method,
    headers,
    body,
    redirect: 'manual',
  });

  const responseHeaders = new Headers(response.headers);
  responseHeaders.set('Access-Control-Allow-Origin', '*');

  return new Response(response.body, {
    status: response.status,
    statusText: response.statusText,
    headers: responseHeaders,
  });
}

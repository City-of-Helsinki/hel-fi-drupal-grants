export class RedirectError extends Error {
  constructor(public readonly redirectUrl: string) {
    super(`Redirecting to ${redirectUrl}`);
    this.name = 'RedirectError';
  }
}

import app from 'flarum/common/app';

export default function purgeLSCache(tags?: string[], paths?: string[]) {
  const queryParams: Record<string, string[]> = {};

  if (tags?.length) {
    queryParams.tags = tags;
  }

  if (paths?.length) {
    queryParams.paths = paths;
  }

  app
    .request({
      url: `${app.forum.attribute('apiUrl')}/lscache-purge`,
      method: 'GET',
      params: queryParams,
    })
    .then(() => {
      app.alerts.show(
        { type: 'success' },
        app.translator.trans(!tags?.length && !paths?.length ? 'acpl-lscache.lib.purge_all_success' : 'acpl-lscache.lib.purge_success')
      );
    });
}

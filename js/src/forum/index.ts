import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';
import LogInModal from 'flarum/forum/components/LogInModal';
import updateCSRF from './utils/updateCSRF';
import SignUpModal from 'flarum/forum/components/SignUpModal';

app.initializers.add('acpl-lscache', () => {
  [LogInModal.prototype, SignUpModal.prototype].forEach((prototype) => {
    extend(prototype, 'oninit', () => {
      updateCSRF().then(() => console.log(app.session.csrfToken));
    });
  });
});

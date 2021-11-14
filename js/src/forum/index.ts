import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';
import LogInModal from 'flarum/forum/components/LogInModal';
import updateCSRF from './utils/updateCSRF';
import SignUpModal from 'flarum/forum/components/SignUpModal';

app.initializers.add('acpl-lscache', () => {
  [LogInModal, SignUpModal].forEach((Component) => {
    extend(Component.prototype, 'oninit', () => {
      updateCSRF();
    });
  });
});

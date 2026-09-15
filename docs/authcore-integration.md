# AlumniCore × AuthCore

AlumniCore does not have a legacy user or login system. AuthCore therefore becomes the application's authentication/account foundation without requiring migration.

## Application identity

AlumniCore registers with AuthCore as:

- Type: `application`
- Application Key: `alumni`
- Application Name: `AlumniCore`

## Optional dependency

AlumniCore remains usable when AuthCore is not installed or active. The integration checks for `AuthCore\\AuthCore` after `plugins_loaded` and otherwise does nothing.

## Admin integration

When AuthCore is active, AlumniCore registers two screens under the existing `alumni-core` admin menu:

- `ユーザー`: common AuthCore user management for the `alumni` application.
- `初期設定`: initial administrator onboarding while the application has zero AuthCore accounts.

The onboarding-created account receives AuthCore's common `admin` capability.

## Installation order

Both orders are supported:

- AlumniCore → AuthCore
- AuthCore → AlumniCore

Registration is delayed until after AuthCore's database migration hook, so application registration does not depend on plugin load order.

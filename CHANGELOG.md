# Changelog

Important changes will be notified in this file

## unreleased
- Fixed: do not record order update events when state hasn't changed.

## 2022-11-07 - 0.5.2
- Fixed: localized variant option title returns full array when locale title wasn't present

## 2022-11-07 - 0.5.1
- Added: dataAsPrimitive() helper method to render data and ensure that a primitive is given, else the default is returned. The default data() method can also return object or array, which can cause - in case of missing translations - unexcepted array returns.

## 2022-11-03 - 0.5.0
First release of the trader package.


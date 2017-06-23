Actions and Filters
===================

This file describes the hooks and filters currently implemented in FlockR.

## Definitions

### Action
An action is a procedural step that can be extended from anywhere in the code by registering a hook for it. Actions do not have a return value.

### Filter
A filter is a functional step that can be extended from anywhere in the code by registering a hook for it. Filters do return values.


## Actions

### init_scheduler
This action is called by the scheduler when the job table is initialized. It can be used to set up new jobs and schedules.

This action takes no parameters.



## Filters

The following filters are defined by FlockR:

### build_menu
Called in order to build the data structure for the main menu. Registered hooks are passed the current data structure as an array and have to return it as a whole with the desired modifications.

### build_sidebar
Called in order to build the sidebar. Returns rendered content.

### get_setting
Called when a setting is retrieved: 

#### Parameters
- value: Value retrieved from DB or cache
- key: Key for the setting

#### Return
Return the filtered value


### set_setting
Called when a setting is stored 

#### Parameters
- value: Value to be stored to DB and cache
- key: Key for the setting

#### Return
Return the filtered value






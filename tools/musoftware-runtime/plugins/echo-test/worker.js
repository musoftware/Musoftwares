'use strict';

// Echo Test Worker — validates the full JSON-line protocol pipeline
const params = JSON.parse(process.env.MUSOFTWARE_PARAMS || '{}');
const emit = (obj) => process.stdout.write(JSON.stringify(obj) + '\n');

emit({ type: 'log', level: 'info', message: `Echo worker started. PARAMS: ${JSON.stringify(params)}` });
emit({ type: 'log', level: 'info', message: `Runtime: ${process.env.MUSOFTWARE_RUNTIME}` });
emit({ type: 'log', level: 'info', message: `Task ID: ${process.env.MUSOFTWARE_TASK_ID}` });
emit({ type: 'progress', percent: 33, message: 'Step 1/3 — receiving params' });

setTimeout(() => {
    emit({ type: 'progress', percent: 66, message: 'Step 2/3 — processing' });
    setTimeout(() => {
        emit({ type: 'progress', percent: 100, message: 'Step 3/3 — done' });
        emit({ type: 'result', data: {
            echo:    params,
            taskId:  process.env.MUSOFTWARE_TASK_ID,
            runtime: process.env.MUSOFTWARE_RUNTIME,
            plugin:  process.env.MUSOFTWARE_PLUGIN_ID,
        }});
    }, 300);
}, 300);

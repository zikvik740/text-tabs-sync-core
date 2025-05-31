
import React from 'react';
import { BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer } from 'recharts';

const ActivityChart = () => {
  const data = [
    { day: 'Пн', created: 45, updated: 28 },
    { day: 'Вт', created: 52, updated: 34 },
    { day: 'Ср', created: 38, updated: 22 },
    { day: 'Чт', created: 63, updated: 41 },
    { day: 'Пт', created: 71, updated: 45 },
    { day: 'Сб', created: 29, updated: 18 },
    { day: 'Вс', created: 24, updated: 15 },
  ];

  return (
    <div className="h-64">
      <ResponsiveContainer width="100%" height="100%">
        <BarChart data={data}>
          <CartesianGrid strokeDasharray="3 3" stroke="#f0f0f0" />
          <XAxis 
            dataKey="day" 
            stroke="#888888"
            fontSize={12}
          />
          <YAxis 
            stroke="#888888"
            fontSize={12}
          />
          <Tooltip 
            contentStyle={{
              backgroundColor: 'white',
              border: '1px solid #e2e8f0',
              borderRadius: '8px',
              boxShadow: '0 4px 6px -1px rgba(0, 0, 0, 0.1)'
            }}
          />
          <Bar dataKey="created" fill="#3b82f6" name="Создано" radius={[2, 2, 0, 0]} />
          <Bar dataKey="updated" fill="#10b981" name="Обновлено" radius={[2, 2, 0, 0]} />
        </BarChart>
      </ResponsiveContainer>
    </div>
  );
};

export { ActivityChart };

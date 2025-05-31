
import React from 'react';
import { Badge } from '@/components/ui/badge';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';

const RecentUsers = () => {
  const users = [
    {
      id: 1,
      email: 'user1@example.com',
      status: 'verified',
      createdAt: '2024-01-15',
      pagesCount: 12
    },
    {
      id: 2,
      email: 'user2@example.com',
      status: 'pending',
      createdAt: '2024-01-14',
      pagesCount: 3
    },
    {
      id: 3,
      email: 'user3@example.com',
      status: 'verified',
      createdAt: '2024-01-14',
      pagesCount: 8
    },
    {
      id: 4,
      email: 'user4@example.com',
      status: 'verified',
      createdAt: '2024-01-13',
      pagesCount: 15
    },
    {
      id: 5,
      email: 'user5@example.com',
      status: 'pending',
      createdAt: '2024-01-13',
      pagesCount: 1
    }
  ];

  const getStatusBadge = (status: string) => {
    if (status === 'verified') {
      return <Badge className="bg-green-100 text-green-800 hover:bg-green-100">Подтвержден</Badge>;
    }
    return <Badge variant="secondary">Ожидает</Badge>;
  };

  const getInitials = (email: string) => {
    return email.substring(0, 2).toUpperCase();
  };

  return (
    <div className="space-y-4">
      {users.map((user) => (
        <div key={user.id} className="flex items-center justify-between p-4 rounded-lg border border-border hover:bg-accent/50 transition-colors">
          <div className="flex items-center space-x-4">
            <Avatar>
              <AvatarFallback>{getInitials(user.email)}</AvatarFallback>
            </Avatar>
            <div>
              <p className="text-sm font-medium">{user.email}</p>
              <p className="text-xs text-muted-foreground">
                Регистрация: {new Date(user.createdAt).toLocaleDateString('ru-RU')}
              </p>
            </div>
          </div>
          
          <div className="flex items-center space-x-4">
            <div className="text-right">
              <p className="text-sm font-medium">{user.pagesCount}</p>
              <p className="text-xs text-muted-foreground">страниц</p>
            </div>
            {getStatusBadge(user.status)}
          </div>
        </div>
      ))}
    </div>
  );
};

export { RecentUsers };
